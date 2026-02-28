import os
import time
import re
from dotenv import load_dotenv

from .retriever import get_dskp_context, get_vark_prompt, get_question_template, retrieve_similar_examples
from .llm_provider import get_provider, reset_provider

# Load environment variables
load_dotenv() 

# --- LLM Provider Setup ---
# Get the provider instance (will be initialized on first use)
provider = get_provider()

# --- Word Limit Configuration per TP ---
def get_word_limit_for_tp(mastery_level, vark_style=None):
    """
    Get the maximum word count for question text based on TP (Teaching Point).
    Word limits are general and apply to all VARK styles.
    
    Args:
        mastery_level: Mastery level (e.g., 'TP1', 'TP2', 'TP3_easy', 'TP6_hard')
        vark_style: VARK learning style (optional, not used for word limits - kept for API compatibility)
    
    Returns:
        int: Maximum word count for the question text
    """
    # Extract base TP number (e.g., "TP1_easy" -> "TP1", "TP6" -> "TP6")
    import re
    tp_match = re.match(r'TP(\d+)', mastery_level)
    if not tp_match:
        # Default to TP3 if can't parse
        tp_number = 3
    else:
        tp_number = int(tp_match.group(1))
    
    # General word limits per TP (applies to all VARK styles)
    word_limits = {
        1: 30,   # TP1: Easiest - keep questions very simple and short
        2: 35,   # TP2: Still easy - keep concise
        3: 45,   # TP3: Moderate difficulty
        4: 50,   # TP4: Getting more complex
        5: 55,  # TP5: Complex scenarios allowed
        6: 60   # TP6: Hardest - can have more detailed scenarios
    }
    
    return word_limits.get(tp_number, 100)  # Default to 100 if TP out of range

def parse_question_to_json(raw_text, topic, mastery_level, vark_style, question_format='multiple_choice', subtopic=None):
    """
    Parse the AI-generated question text into structured JSON format.
    Supports two formats: multiple_choice, subjective
    """
    try:
        lines = raw_text.strip().split('\n')
        
        # Extract question text (collect everything after "Question:" until next section)
        question_text = ""
        i = 0
        
        # Find "Question:" line
        while i < len(lines):
            line = lines[i].strip()
            if line.startswith("Question:"):
                question_text = line.replace("Question:", "").strip()
                i += 1
                
                # Continue collecting question text until we hit a section header
                section_headers = ['Model Answer:', 'Expected Answer:', 'Marking Points:', 'Marking Scheme:', 
                                'Complete Solution:', 'Step 1:', 'Final Answer:', 'Total:', 'Type:', 'Answer:',
                                'A.', 'B.', 'C.', 'D.']  # Include choice markers too
                
                while i < len(lines):
                    next_line = lines[i].strip()
                    
                    # Check if multiple choice format (A., B., C., D.)
                    if question_format == 'multiple_choice' and re.match(r'^[A-D]\.', next_line):
                        break
                    
                    # Check if we hit a section header
                    if any(next_line.startswith(header) for header in section_headers):
                        break
                    
                    # If it's not empty, add to question text
                    if next_line:
                        question_text += " " + next_line
                    
                    i += 1
                break
            i += 1
        
        if not question_text:
            return None, "Failed to extract question text"
        
        # === FORMAT 1: Multiple Choice ===
        if question_format == 'multiple_choice':
            choices = {}
            answer_key = ""
            
            # Continue collecting question text until we hit choices
            while i < len(lines):
                next_line = lines[i].strip()
                if next_line and not re.match(r'^[A-D]\.', next_line):
                    question_text += " " + next_line
                    i += 1
                else:
                    break
            
            # Extract choices A-D
            while i < len(lines):
                line = lines[i].strip()
                
                # Match choice pattern: A. text or A) text
                choice_match = re.match(r'^([A-D])[\.\)]\s*(.+)', line)
                if choice_match:
                    choice_letter = choice_match.group(1)
                    choice_text = choice_match.group(2).strip()
                    choices[choice_letter] = choice_text
                
                # Match answer pattern: Answer: A or Answer: A.
                answer_match = re.match(r'^Answer:\s*([A-D])', line, re.IGNORECASE)
                if answer_match:
                    answer_key = answer_match.group(1).upper()
                
                i += 1
            
            # Validation
            if len(choices) != 4:
                return None, f"Expected 4 choices, found {len(choices)}"
            if not answer_key or answer_key not in ['A', 'B', 'C', 'D']:
                return None, f"Invalid or missing answer key: {answer_key}"
            
            # Convert choices to list format with id
            choices_list = [
                {"id": 1, "label": "A", "text": choices.get("A", "")},
                {"id": 2, "label": "B", "text": choices.get("B", "")},
                {"id": 3, "label": "C", "text": choices.get("C", "")},
                {"id": 4, "label": "D", "text": choices.get("D", "")}
            ]
            
            return {
                "question_text": question_text,
                "choices": choices_list,
                "answer_key": answer_key,
                "topic_id": topic,
                "subtopic_id": subtopic,
                "mastery_level": mastery_level,
                "learning_style": vark_style,
                "question_format": question_format,
                "raw_response": raw_text
            }, None
        
        # === FORMAT 2: Subjective (with steps and final answer) ===
        elif question_format == 'subjective':
            working_steps = {}
            final_answer = ""
            total_marks = 0
            answer_type = "text"
            
            step_counter = 1
            while i < len(lines):
                line = lines[i].strip()
                
                # Match step patterns: "Step 1:", "Step 2:", etc.
                step_match = re.match(r'^Step\s+(\d+):\s*(.+)', line, re.IGNORECASE)
                if step_match:
                    step_num = step_match.group(1)
                    step_text = step_match.group(2).strip()
                    working_steps[f"step_{step_num}"] = step_text
                    step_counter += 1
                
                # Match final answer - more flexible patterns
                if (line.startswith("Final Answer:") or line.startswith("Answer:") or 
                    line.startswith("Jawapan:") or line.startswith("Jawapan Akhir:") or
                    re.match(r'^.*Answer.*:', line, re.IGNORECASE)):
                    final_answer = line.split(":", 1)[1].strip()
                
                # Check for answer type hint
                if line.startswith("Type:") or line.startswith("Answer Type:"):
                    type_text = line.split(":", 1)[1].strip().lower()
                    if "numeric" in type_text or "number" in type_text:
                        answer_type = "numeric"
                    elif "text" in type_text or "word" in type_text:
                        answer_type = "text"
                
                # Match total marks
                if line.startswith("Total:"):
                    marks_match = re.search(r'(\d+)', line)
                    if marks_match:
                        total_marks = int(marks_match.group(1))
                
                i += 1
            
            # If no explicit final answer found, try to extract from the last step or lines
            if not final_answer:
                # Look for answer patterns in the working steps or remaining lines
                lines_to_check = list(working_steps.values()) + [line.strip() for line in lines[i-10:i] if line.strip()]
                
                for potential_answer in lines_to_check:
                    # Look for patterns like "= answer", "answer", numbers with units, etc.
                    answer_patterns = [
                        r'=\s*([^,\n]+?)(?:\s*\([^)]*\)|$)',  # = answer (marks)
                        r'(?:adalah|ialah)\s+([^,\n]+?)(?:\s*\([^)]*\)|$)',  # adalah/ialah answer
                        r'([+-]?\s*\d+(?:\.\d+)?(?:\s*[a-zA-Z°%/]+)?)\s*(?:\([^)]*\)|$)',  # numeric with units
                        r'([A-Za-z\s]+)\s*(?:\([^)]*\)|$)'  # text answer
                    ]
                    
                    for pattern in answer_patterns:
                        match = re.search(pattern, potential_answer, re.IGNORECASE)
                        if match:
                            final_answer = match.group(1).strip()
                            break
                    
                    if final_answer:
                        break
            
            if not final_answer:
                return None, f"Failed to extract final answer for subjective format. Steps found: {list(working_steps.keys())}"
            
            # Auto-detect answer type if not explicitly stated
            if answer_type == "text":
                # Check if answer looks numeric (contains digits, +/-, decimals)
                if re.match(r'^[−\-+]?\s*\d+(\.\d+)?$', final_answer.strip()):
                    answer_type = "numeric"
            
            # Clean the final answer and generate accepted variations
            # Remove marks notation like "(1 mark)" from final answer for variations
            clean_answer = re.sub(r'\s*\([^)]*mark[^)]*\)', '', final_answer, flags=re.IGNORECASE).strip()
            
            accepted_variations = []
            
            if answer_type == "numeric":
                # Extract numeric value and unit separately
                numeric_match = re.search(r'([+-]?\s*\d+(?:\.\d+)?(?:/\d+)?)\s*([a-zA-Z°%]*)', clean_answer)
                if numeric_match:
                    number_part = numeric_match.group(1).strip()
                    unit_part = numeric_match.group(2).strip()
                    
                    # Generate variations with different formats
                    base_variations = []
                    
                    # Clean number (remove spaces around signs)
                    clean_number = number_part.replace(" ", "")
                    base_variations.append(clean_number)
                    
                    # Add version with space after sign
                    if clean_number.startswith("-") or clean_number.startswith("+"):
                        spaced_number = clean_number[0] + " " + clean_number[1:]
                        base_variations.append(spaced_number)
                    
                    # Add unicode minus version
                    unicode_number = clean_number.replace("-", "−")
                    if unicode_number != clean_number:
                        base_variations.append(unicode_number)
                    
                    # Create variations with and without units
                    for num_var in base_variations:
                        # Just the number
                        accepted_variations.append(num_var)
                        # With unit if unit exists
                        if unit_part:
                            accepted_variations.append(f"{num_var}{unit_part}")
                            accepted_variations.append(f"{num_var} {unit_part}")
                else:
                    # Fallback for complex numeric answers
                    accepted_variations.append(clean_answer)
            else:
                # Text answers - just add the clean version
                accepted_variations.append(clean_answer)
            
            # Remove duplicates while preserving order
            accepted_variations = list(dict.fromkeys(accepted_variations))
            
            # Ensure we have at least the clean answer
            if not accepted_variations:
                accepted_variations = [clean_answer]
            
            return {
                "question_text": question_text,
                "working_steps": working_steps,
                "final_answer": final_answer,
                "answer_type": answer_type,
                "accepted_variations": accepted_variations,
                "total_marks": total_marks or len(working_steps) + 1,  # +1 for final answer
                "topic_id": topic,
                "subtopic_id": subtopic,
                "mastery_level": mastery_level,
                "learning_style": vark_style,
                "question_format": question_format,
                "raw_response": raw_text
            }, None
        
        else:
            return None, f"Unknown question format: {question_format}"
    
    except Exception as e:
        return None, f"Error parsing question: {str(e)}"

def generate_rag_question(student_data):
    """
    Performs the RAG process: Retrieval, Augmentation, and Generation using the configured LLM provider
    with exponential backoff for transient server errors (500/503).
    """
    # Get or refresh provider instance
    global provider
    if provider is None:
        provider = get_provider()
    
    if provider is None:
        provider_name = os.getenv('LLM_PROVIDER', 'gemini')
        return {
            "status": "error",
            "message": f"LLM provider ({provider_name}) not initialized. Check API key environment variable.",
            "full_prompt": ""
        }
    
    #safe fallbacks, kalau tkde pape respon dari front-end
    topic = student_data.get('topic', 'Topic1')
    subtopic = student_data.get('subtopic', None)  # NEW: Subtopic code (e.g., '1.1', '1.2')
    mastery_level = student_data.get('mastery_level', 'TP3')
    vark_style = student_data.get('vark_style', 'Read')
    question_format = student_data.get('question_format', 'multiple_choice')  # NEW: multiple_choice, subjective
    language = student_data.get('language', 'english')  # NEW: english or malay

    # --- R: Retrieval ---
    dskp_context, complexity = get_dskp_context(topic, mastery_level, subtopic)
    vark_context = get_vark_prompt(vark_style)
    question_template = get_question_template(topic, mastery_level, subtopic)
    
    # 🔥 NEW: Retrieve similar examples from CSV dataset (TRUE RAG!)
    similar_examples = retrieve_similar_examples(mastery_level, vark_style, num_examples=2)

    # --- A: Augmentation (Building the Dynamic Prompt) ---
    
    # Build question type guidance
    question_types_text = ""
    if question_template['question_types']:
        question_types_text = "Possible question types for this level:\n" + "\n".join(
            [f"        - {qt}" for qt in question_template['question_types']]
        )
    
    # Build constraints text
    constraints_text = ""
    if question_template['constraints']:
        constraints_text = "Additional constraints:\n" + "\n".join(
            [f"        - {k}: {v}" for k, v in question_template['constraints'].items()]
        )
    
    # Add word limit constraint based on TP (general for all VARK styles)
    word_limit = get_word_limit_for_tp(mastery_level, vark_style)
    constraints_text += f"\n        - MAX {word_limit} words for question text (STRICT LIMIT - count carefully)"
    constraints_text += "\n        - Focus on math, not story"
    
    # Build example text
    example_text = ""
    if question_template['example']:
        example_text = f"\n        Example for this difficulty level:\n        {question_template['example']}"
    
    # 🔥 NEW: Add retrieved examples from dataset (RAG augmentation)
    retrieved_examples_text = ""
    if similar_examples:
        retrieved_examples_text = "\n\n        📚 REFERENCE EXAMPLES from Dataset (use these ONLY as style/structure guides, NOT to copy scenarios):"
        for idx, ex in enumerate(similar_examples, 1):
            retrieved_examples_text += f"\n        Example {idx} ({ex['mastery_level']}, {ex['learning_type']}):"
            retrieved_examples_text += f"\n        Q: {ex['question']}"
            if ex.get('working'):
                retrieved_examples_text += f"\n        Working: {ex['working']}"
            retrieved_examples_text += f"\n        A: {ex['answer']}\n"
    
    # Add creativity and diversity instructions
    creativity_instructions = """
        🎨 CREATIVITY & DIVERSITY REQUIREMENTS (CRITICAL):
        - Use the examples above ONLY as style/structure references. DO NOT copy or repeat their scenarios.
        - Create a COMPLETELY NEW and DIFFERENT scenario/context from the examples.
        - Vary real-world contexts: Use different situations (e.g., shopping, sports, cooking, travel, weather, 
          measurements, money, time, distance, temperature, etc.) - NOT just the same context from examples.
        - If examples mention "diver", use a DIFFERENT context like "hiker", "swimmer", "climber", "temperature", 
          "elevator", "bank account", "shopping cart", "recipe", "journey", etc.
        - Be creative with numbers, names, and situations - make each question feel fresh and unique.
        - Avoid repetitive scenarios even if they test the same math concept.
        - Think of diverse Malaysian contexts: markets, transportation, sports, daily activities, etc.
    """
    
    # Question format instructions (extensible section)
    question_format_instructions = """
        📝 QUESTION FORMAT INSTRUCTIONS:
        - Write questions naturally without explicit mathematical notation in parentheses.
        - DO NOT include notations like (+15), (-5), (+8) in the question text.
        - Instead, describe the situation naturally (e.g., "gains 15 points" instead of "gains 15 points (+15)").
        - Use natural language to describe positive/negative situations without showing the sign notation.
        - Keep questions clean and readable without mathematical symbols in the narrative.
    """
    
    # Determine output format based on question_format
    if question_format == 'multiple_choice':
        format_instructions = """Output Format (FOLLOW EXACTLY):
                                Question: [question text]
                                A. [choice A]
                                B. [choice B]
                                C. [choice C]
                                D. [choice D]
                                Answer: [A/B/C/D]
                                
                                Make the answer choices as similar as possible to the question text.
                                """
    elif question_format == 'subjective':
        # Get word limit for subjective format too
        word_limit = get_word_limit_for_tp(mastery_level, vark_style)
        format_instructions = f"""Output Format (MUST FOLLOW EXACTLY):
                                Question: [Concise question - max {word_limit} words]
                                Step 1: [What to calculate] (1 mark)
                                Step 2: [What to calculate] (1 mark)
                                Step 3: [What to calculate] (1 mark)
                                Final Answer: [Number with units] (1 mark)
                                Type: [numeric OR text]
                                Total: [X marks]
                                
                                Example:
                                Question: Ahmad deposited RM150, withdrew RM75, then deposited RM50. Calculate his final balance.
                                Step 1: Initial deposit: RM150 (1 mark)
                                Step 2: After withdrawal: RM150 - RM75 = RM75 (1 mark)
                                Step 3: After second deposit: RM75 + RM50 = RM125 (1 mark)
                                Final Answer: RM125 (1 mark)
                                Type: numeric
                                Total: 4 marks
                                
                                CRITICAL: Be concise. Show clear working steps. Question text must be MAX {word_limit} words."""
    else:
        format_instructions = """Output Format (FOLLOW EXACTLY):
                                Question: [question text]
                                A. [choice A]
                                B. [choice B]
                                C. [choice C]
                                D. [choice D]
                                Answer: [A/B/C/D]"""
    
    # Determine language instruction
    language_instruction = "Generate in English." if language.lower() == "english" else "Generate in Bahasa Malaysia (Malay)."

    base_instructions = f"""
            Math teacher for Form 1 students. Generate ONE question.

            {dskp_context.strip()}
            {vark_context.strip()}
            {question_template['subtopic_context']}
            {question_template['general_instructions']}
            {question_template['notation_guide']}

            {question_types_text}
            {constraints_text}
            {example_text}
            {retrieved_examples_text}
            {creativity_instructions}
            {question_format_instructions}

            {format_instructions}

            {language_instruction}
            Be concise. Generate now:
            """
    
    # --- G: Generation (LLM API Call with Backoff) ---
    max_retries = 5
    base_delay = 3 # initial delay in seconds

    for attempt in range(max_retries):
        try:
            # Suppress verbose output, only show on retry
            if attempt > 0:
                print(f"Retry {attempt}/{max_retries}...")
            
            # Use provider abstraction to generate content
            result = provider.generate_content(
                prompt=base_instructions,
                temperature=0.7,
                max_tokens=4096  # Optimized for free tier - sufficient for all question types
            )
            
            # Check for errors
            if result.get('error'):
                error_msg = result['error']
                is_transient = result.get('is_transient', False)
                
                # Handle transient errors with retry
                if is_transient:
                    if attempt < max_retries - 1:
                        delay = base_delay * (2 ** attempt)
                        print(f"Server unavailable. Retrying in {delay:.2f} seconds...")
                        time.sleep(delay)
                        continue
                    else:
                        return {
                            "status": "error",
                            "message": f"LLM API failed after {max_retries} attempts: Server consistently unavailable.",
                        }
                else:
                    # Non-transient error (Auth, Bad Request, etc.). Do not retry.
                    return {
                        "status": "error",
                        "message": f"LLM API request failed with non-transient error: {error_msg}",
                    }
            
            # Success case: response text is present
            response_text = result.get('text')
            finish_reason = result.get('finish_reason')
            
            if response_text:
                # Check for truncation warnings
                if finish_reason in ['MAX_TOKENS', 'max_tokens']:
                    print("⚠ Warning: Response was truncated (MAX_TOKENS), but returning available text")
                
                # Parse the raw text into structured JSON
                parsed_question, parse_error = parse_question_to_json(
                    response_text, 
                    topic, 
                    mastery_level, 
                    vark_style,
                    question_format,
                    subtopic
                )
                
                if parsed_question:
                    return {
                        "status": "success",
                        "data": parsed_question,
                        # RAG metadata
                        "rag_metadata": {
                            "retrieved_examples_count": len(similar_examples),
                            "examples_used": [
                                {
                                    "mastery": ex['mastery_level'],
                                    "learning_type": ex['learning_type'],
                                    "context": ex['context']
                                } for ex in similar_examples
                            ]
                        },
                        # Keep these for backward compatibility/debugging
                        "dskp_context_used": dskp_context.strip(),
                        "vark_context_used": vark_context.strip()
                    }
                else:
                    # Parsing failed, return raw text as fallback
                    print("\n⚠️ PARSING FAILED - Raw AI Output:")
                    print("="*60)
                    print(response_text)
                    print("="*60)
                    return {
                        "status": "warning",
                        "message": f"Question generated but parsing failed: {parse_error}",
                        "raw_question": response_text,
                        "dskp_context_used": dskp_context.strip(),
                        "vark_context_used": vark_context.strip()
                    }
            
            # Safety block or empty response case
            if finish_reason in ['SAFETY', 'content_filter']:
                return {
                    "status": "error",
                    "message": "LLM returned an empty response due to safety filtering.",
                }
            
            # No text and no error - unexpected case
            return {
                "status": "error",
                "message": "LLM returned empty response with no error message.",
            }
        
        except Exception as e:
            # Catch any other unexpected Python errors
            return {
                "status": "error",
                "message": f"An unexpected error occurred during API call: {e}",
            }

    # Fallback return if loop logic somehow fails (shouldn't happen with the logic above)
    return {
        "status": "error",
        "message": "Failed to generate question after all retries. Review server logs.",
        # "full_prompt": base_instructions
    }

def generate_batch_questions(student_data, mastery_levels_list):
    """
    Generate multiple questions in a SINGLE API call.
    
    Args:
        student_data: Dict with topic, vark_style, question_format, etc.
        mastery_levels_list: List of mastery levels for each question (e.g., ['TP1', 'TP1', 'TP2', 'TP2'])
    
    Returns:
        Dict with status, list of parsed questions, and metadata
    """
    # Get or refresh provider instance
    global provider
    if provider is None:
        provider = get_provider()
    
    if provider is None:
        provider_name = os.getenv('LLM_PROVIDER', 'gemini')
        return {
            "status": "error",
            "message": f"LLM provider ({provider_name}) not initialized. Check API key environment variable.",
            "questions": []
        }
    
    topic = student_data.get('topic', 'Topic1')
    subtopic = student_data.get('subtopic', None)
    vark_style = student_data.get('vark_style', 'Read')
    question_format = student_data.get('question_format', 'multiple_choice')
    language = student_data.get('language', 'english')
    num_questions = len(mastery_levels_list)
    
    # Count questions per mastery level for the prompt
    from collections import Counter
    level_counts = Counter(mastery_levels_list)
    level_breakdown = ", ".join([f"{count} {level}" for level, count in level_counts.items()])
    
    # --- R: Retrieval (get context for ALL mastery levels) ---
    # Get DSKP context for each unique mastery level
    unique_levels = list(set(mastery_levels_list))
    dskp_contexts = {}
    for level in unique_levels:
        context, complexity = get_dskp_context(topic, level, subtopic)
        dskp_contexts[level] = context
    
    vark_context = get_vark_prompt(vark_style)
    
    # Get similar examples for each mastery level
    all_examples = []
    for level in unique_levels:
        examples = retrieve_similar_examples(level, vark_style, num_examples=1)
        if examples:
            all_examples.extend(examples)
    
    # --- A: Augmentation (Build batch prompt) ---
    
    # Build DSKP context for all levels
    dskp_text = "DSKP Context for each level:\n"
    for level in unique_levels:
        dskp_text += f"\n{level}:\n{dskp_contexts[level].strip()}\n"
    
    # Build examples text
    examples_text = ""
    if all_examples:
        examples_text = "\n📚 REFERENCE EXAMPLES from Dataset (use these ONLY as style/structure guides, NOT to copy scenarios):"
        for idx, ex in enumerate(all_examples, 1):
            examples_text += f"\nExample {idx} ({ex['mastery_level']}, {ex['learning_type']}):"
            examples_text += f"\nQ: {ex['question']}"
            if ex.get('working'):
                examples_text += f"\nWorking: {ex['working']}"
            examples_text += f"\nA: {ex['answer']}\n"
    
    # Build creativity and diversity instructions for batch
    batch_creativity_instructions = f"""
🎨 CREATIVITY & DIVERSITY REQUIREMENTS (CRITICAL):
- Use the examples above ONLY as style/structure references. DO NOT copy or repeat their scenarios.
- Each of the {num_questions} questions MUST have a COMPLETELY DIFFERENT scenario/context.
- Vary real-world contexts across questions: Use different situations (shopping, sports, cooking, travel, weather, 
  measurements, money, time, distance, temperature, elevators, bank accounts, recipes, journeys, etc.)
- If examples mention "diver", create questions with DIFFERENT contexts like "hiker", "swimmer", "climber", 
  "temperature", "elevator", "bank account", "shopping cart", "recipe", "journey", "savings", "height", etc.
- Be creative with numbers, names, and situations - make each question feel fresh and unique.
- Avoid repetitive scenarios even if they test the same math concept.
- Think of diverse Malaysian contexts: markets, transportation, sports, daily activities, etc.
- IMPORTANT: If you generate multiple questions, each must have a UNIQUE context - no two questions should 
  use the same scenario type (e.g., don't make 3 questions all about divers or all about temperature).
"""
    
    # Question format instructions for batch (extensible section)
    batch_question_format_instructions = """
📝 QUESTION FORMAT INSTRUCTIONS:
- Write questions naturally without explicit mathematical notation in parentheses.
- DO NOT include notations like (+15), (-5), (+8) in the question text.
- Instead, describe the situation naturally (e.g., "gains 15 points" instead of "gains 15 points (+15)").
- Use natural language to describe positive/negative situations without showing the sign notation.
- Keep questions clean and readable without mathematical symbols in the narrative.
- Use simple, familiar, and appropriate vocabulary for Form 1 students. For changes in temperature or quantity, use easy words like "increase", "decrease", "goes up", "drops", or "becomes higher/lower" instead of less common words like "rose".
"""
    
    # Build word limit constraints for each unique TP level
    word_limit_constraints = ""
    for level in unique_levels:
        word_limit = get_word_limit_for_tp(level, vark_style)
        word_limit_constraints += f"\n- {level}: MAX {word_limit} words for question text"
    
    # Determine output format
    if question_format == 'multiple_choice':
        format_example = """Question: [question text]
A. [choice A]
B. [choice B]
C. [choice C]
D. [choice D]
Answer: [A/B/C/D]"""
    elif question_format == 'subjective':
        # Use the highest word limit from unique levels for format example
        max_word_limit = max([get_word_limit_for_tp(level, vark_style) for level in unique_levels])
        format_example = f"""Question: [Concise question - max {max_word_limit} words]
Step 1: [What to calculate] (1 mark)
Step 2: [What to calculate] (1 mark)
Step 3: [What to calculate] (1 mark)
Final Answer: [Number with units] (1 mark)
Type: [numeric OR text]
Total: [X marks]"""
    else:
        format_example = """Question: [question text]
A. [choice A]
B. [choice B]
C. [choice C]
D. [choice D]
Answer: [A/B/C/D]"""
    
    # Build question-by-question breakdown with word limits
    question_breakdown = ""
    for i, level in enumerate(mastery_levels_list, 1):
        word_limit = get_word_limit_for_tp(level, vark_style)
        question_breakdown += f"\nQuestion {i}: Use {level} difficulty (MAX {word_limit} words for question text)"

    # Determine language instruction for batch
    batch_language_instruction = "Use English language." if language.lower() == "english" else "Use Bahasa Malaysia (Malay) language."

    # Build batch prompt
    batch_prompt = f"""Math teacher for Form 1 students. Generate {num_questions} DIFFERENT questions ({level_breakdown}).

{dskp_text}

{vark_context.strip()}

{examples_text}
{batch_creativity_instructions}
{batch_question_format_instructions}

REQUIREMENTS:
- Generate exactly {num_questions} questions
- Each question MUST be DIFFERENT (different scenarios, numbers, contexts)
- Follow mastery level distribution:{question_breakdown}
- Word limits per TP level (STRICT - count carefully):{word_limit_constraints}
- {batch_language_instruction}
- Be concise, no storytelling
- Focus on math, not story

OUTPUT FORMAT (CRITICAL - separate each question with "---"):
{format_example}

---

{format_example}

---

(Continue for all {num_questions} questions)

Generate {num_questions} diverse questions NOW:"""
    
    # --- G: Generation with retry logic ---
    max_retries = 5
    base_delay = 3
    
    for attempt in range(max_retries):
        try:
            if attempt > 0:
                print(f"Retry {attempt}/{max_retries}...")
            
            # Adjust max_tokens based on number of questions
            tokens_per_question = 600  # ~500-600 tokens per question
            max_tokens = min(8192, num_questions * tokens_per_question + 500)
            
            # Use provider abstraction to generate content
            result = provider.generate_content(
                prompt=batch_prompt,
                temperature=0.7,
                max_tokens=max_tokens
            )
            
            # Check for errors
            if result.get('error'):
                error_msg = result['error']
                is_transient = result.get('is_transient', False)
                status_code = result.get('status_code')
                
                # Handle transient errors with retry
                if is_transient:
                    if attempt < max_retries - 1:
                        delay = base_delay * (2 ** attempt)
                        status_info = f" (Status: {status_code})" if status_code else ""
                        print(f"Server unavailable{status_info}. Error: {error_msg[:100]}...")
                        print(f"Retrying in {delay:.2f} seconds...")
                        time.sleep(delay)
                        continue
                    else:
                        return {
                            "status": "error",
                            "message": f"LLM API failed after {max_retries} attempts: Server consistently unavailable (Status: {status_code}). Last error: {error_msg[:200]}",
                            "questions": []
                        }
                else:
                    return {
                        "status": "error",
                        "message": f"LLM API request failed: {error_msg}",
                        "questions": []
                    }
            
            # Success case: response text is present
            response_text = result.get('text')
            finish_reason = result.get('finish_reason')
            
            if response_text:
                # Check for truncation warnings
                if finish_reason in ['MAX_TOKENS', 'max_tokens']:
                    print("⚠ Warning: Response was truncated (MAX_TOKENS)")
                
                # Parse multiple questions from response
                raw_text = response_text
                
                # Split by separator (--- or ===)
                question_blocks = re.split(r'\n---+\n|\n===+\n', raw_text)
                
                parsed_questions = []
                for i, block in enumerate(question_blocks):
                    if not block.strip():
                        continue
                    
                    # Use the mastery level for this question
                    level = mastery_levels_list[i] if i < len(mastery_levels_list) else mastery_levels_list[-1]
                    
                    parsed, error = parse_question_to_json(
                        block.strip(),
                        topic,
                        level,
                        vark_style,
                        question_format,
                        subtopic
                    )
                    
                    if parsed:
                        parsed_questions.append(parsed)
                    else:
                        print(f"⚠️ Failed to parse question {i+1}: {error}")
                
                if parsed_questions:
                    return {
                        "status": "success",
                        "questions": parsed_questions,
                        "total_parsed": len(parsed_questions),
                        "total_requested": num_questions,
                        "rag_metadata": {
                            "retrieved_examples_count": len(all_examples),
                            "mastery_levels_used": unique_levels
                        }
                    }
                else:
                    print("\n⚠️ PARSING FAILED - Raw AI Output:")
                    print("="*60)
                    print(raw_text)
                    print("="*60)
                    return {
                        "status": "warning",
                        "message": "Questions generated but parsing failed",
                        "questions": [],
                        "raw_response": raw_text
                    }
            
            # Safety block or empty response case
            if finish_reason in ['SAFETY', 'content_filter']:
                return {
                    "status": "error",
                    "message": "LLM returned an empty response due to safety filtering.",
                    "questions": []
                }
            
            # No text and no error - unexpected case
            return {
                "status": "error",
                "message": "LLM returned empty response with no error message.",
                "questions": []
            }
        
        except Exception as e:
            return {
                "status": "error",
                "message": f"An unexpected error occurred: {e}",
                "questions": []
            }
    
    return {
        "status": "error",
        "message": "Failed to generate questions after all retries.",
        "questions": []
    }