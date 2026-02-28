import json
import os
import csv
import random

# Define the base path for the knowledge base relative to this script's location
KB_PATH = os.path.join(os.path.dirname(__file__), '..', 'knowledge_base')
DATA_PATH = os.path.join(os.path.dirname(__file__), '..', 'data')

def load_json(filename):
    """Loads a JSON file from the knowledge_base directory."""
    filepath = os.path.join(KB_PATH, filename)
    try:
        with open(filepath, 'r') as f:
            return json.load(f)
    except FileNotFoundError:
        print(f"Error: Knowledge file not found at {filepath}")
        return {}

def get_question_template(topic, mastery_level, subtopic=None):
    """
    Retrieves question template instructions based on topic, subtopic, and mastery level.
    
    Args:
        topic: Topic identifier (e.g., 'Topic1')
        mastery_level: Mastery level code (e.g., 'TP1', 'TP2', ..., 'TP6')
        subtopic: Subtopic code (e.g., '1.1', '1.2', etc.) - optional
    
    Returns:
        Dictionary with template data
    """
    template_data = load_json('question_templates.json')
    
    # Get topic-specific data
    topic_data = template_data.get('topics', {}).get(topic, {})
    
    if not topic_data:
        return {
            'general_instructions': f"Generate a {mastery_level} level question about {topic}.",
            'question_types': [],
            'example': "",
            'subtopic_context': "",
            'common_contexts': []
        }
    
    # If subtopic is provided, use subtopic-specific data
    if subtopic:
        subtopic_data = topic_data.get('subtopics', {}).get(subtopic, {})
        if subtopic_data:
            # Map mastery level to difficulty level for subtopic structure
            difficulty_map = {
                'TP1': 'Beginner',
                'TP2': 'Beginner',
                'TP3': 'Proficient',
                'TP4': 'Proficient',
                'TP5': 'Advanced',
                'TP6': 'Advanced'
            }
            difficulty = difficulty_map.get(mastery_level, 'Proficient')
            
            # Get difficulty-specific data from subtopic
            difficulty_data = subtopic_data.get('difficulty_specific', {}).get(difficulty, {})
            
            subtopic_context = f"""
        Subtopic: {subtopic_data.get('name')} ({subtopic_data.get('code')})
        Description: {subtopic_data.get('description')}
        Focus: {subtopic_data.get('focus')}
        Notation: {subtopic_data.get('notation_guide')}"""
            
            return {
                'general_instructions': f"Focus on {subtopic_data.get('name')}. {subtopic_data.get('focus')}",
                'notation_guide': subtopic_data.get('notation_guide', ''),
                'common_contexts': subtopic_data.get('common_contexts', []),
                'question_types': difficulty_data.get('question_types', []),
                'constraints': {k: v for k, v in difficulty_data.items() if k != 'question_types'},
                'example': subtopic_data.get('example_questions', {}).get(difficulty, ''),
                'subtopic_context': subtopic_context
            }
    
    # If no subtopic, get question types from DSKP mastery data (single source of truth)
    # Extract base TP from mastery_level (e.g., "TP1_easy" -> "TP1")
    base_tp = mastery_level
    sub_level = None
    if '_' in mastery_level:
        parts = mastery_level.split('_', 1)
        base_tp = parts[0]
        sub_level = parts[1] if parts[1] in ['easy', 'hard'] else None
    
    # Load DSKP mastery data to get question types
    dskp_data = load_json('dskp_F1_T1_mastery.json')
    mastery_levels = dskp_data.get('mastery_levels', {})
    base_level_data = mastery_levels.get(base_tp, {})
    
    # Get question types from appropriate level
    question_types = []
    if sub_level and base_level_data:
        levels = base_level_data.get('levels', {})
        sub_level_data = levels.get(sub_level)
        if sub_level_data:
            question_types = sub_level_data.get('typical_operations', [])
    elif base_level_data:
        question_types = base_level_data.get('typical_operations', [])
    
    return {
        'general_instructions': topic_data.get('general_instructions', ''),
        'notation_guide': topic_data.get('notation_guide', ''),
        'common_contexts': topic_data.get('common_contexts', []),
        'question_types': question_types,
        'constraints': {},
        'example': '',
        'subtopic_context': ""
    }

def get_dskp_context(topic, mastery_level, subtopic=None):
    """
    Retrieves the DSKP mastery standards and complexity with optional subtopic context.
    
    Args:
        topic: Topic identifier (e.g., 'Topic1' for Rational Numbers)
        mastery_level: Mastery level code (e.g., 'TP1', 'TP2', ..., 'TP6', 'TP1_easy', 'TP1_hard')
        subtopic: Subtopic code (e.g., '1.1', '1.2', etc.) - optional
    
    Returns:
        Tuple of (formatted_context_string, complexity_level)
    """
    # Load the mastery level data for Topic 1 (Rational Numbers)
    dskp_data = load_json('dskp_F1_T1_mastery.json')
    
    # Get mastery levels
    mastery_levels = dskp_data.get('mastery_levels', {})
    
    # Parse mastery level to extract base TP and sub-level (easy/hard)
    base_tp = mastery_level
    sub_level = None
    
    # Check if mastery_level contains a sub-level (e.g., "TP1_easy" or "TP1_hard")
    if '_' in mastery_level:
        parts = mastery_level.split('_', 1)
        base_tp = parts[0]
        sub_level = parts[1] if parts[1] in ['easy', 'hard'] else None
    
    # Get the base mastery level data, default to TP3 if not found
    base_level_data = mastery_levels.get(base_tp, mastery_levels.get('TP3', {}))
    
    # If sub-level is specified and exists, use it; otherwise use base level data
    level_data = base_level_data.copy() if base_level_data else {}
    
    if sub_level and base_level_data:
        levels = base_level_data.get('levels', {})
        sub_level_data = levels.get(sub_level)
        if sub_level_data:
            # Merge sub-level data with base level data (sub-level overrides base)
            level_data.update({
                'description': sub_level_data.get('description', level_data.get('description')),
                'focus': sub_level_data.get('focus', level_data.get('focus')),
                'typical_operations': sub_level_data.get('typical_operations', level_data.get('typical_operations')),
                'complexity_modifier': sub_level_data.get('complexity_modifier', '')
            })
            # Update complexity to reflect sub-level
            base_complexity = level_data.get('complexity', 'MEDIUM')
            modifier = sub_level_data.get('complexity_modifier', '')
            if modifier:
                level_data['complexity'] = f"{base_complexity} ({modifier})"
    
    # Get subtopic context if provided
    subtopic_info = ""
    if subtopic:
        subtopics = dskp_data.get('subtopics', {})
        subtopic_data = subtopics.get(subtopic, {})
        if subtopic_data:
            subtopic_info = f"\n            Subtopic: {subtopic_data.get('name')} ({subtopic_data.get('code')})\n            Subtopic Focus: {subtopic_data.get('description')}"
    
    # Format the DSKP context for the LLM prompt
    if level_data:
        level_display = f"{level_data.get('code')}"
        if sub_level:
            level_display += f" ({sub_level})"
        
        context = f"""
            DSKP Mastery Level: {level_data.get('name')} ({level_display})
            Difficulty Level: {level_data.get('complexity')}
            Focus: {level_data.get('description')}
            Cognitive Level: {level_data.get('cognitive_level')}{subtopic_info}
        """
        return context, level_data.get('complexity', 'MEDIUM')
    
    return "DSKP Focus: Standard curriculum guidelines for the given topic.", "MEDIUM"

def get_vark_prompt(style):
    """Retrieves the VARK instruction prompt based on the student's learning style."""
    vark_data = load_json('vark_templates.json')
    
    # Safely retrieve the instruction based on style
    context = vark_data.get(style, vark_data.get('Read')) # Default to Read
    
    # Format the VARK context for the LLM prompt
    if context:
        return f"Learning Style Constraint ({style}): {context.get('instruction')}"
    
    return "Learning Style Constraint: None specified, use a standard word problem format."

def retrieve_similar_examples(mastery_level, learning_style, num_examples=3):
    """
    🔑 RAG RETRIEVAL FUNCTION
    Retrieves similar question examples from the CSV dataset.
    
    Args:
        mastery_level: Target mastery level (TP1-TP6, or TP1_easy, TP1_hard, etc.)
        learning_style: VARK learning style (R, V, A, K)
        num_examples: Number of examples to retrieve (default: 3)
    
    Returns:
        List of example questions with full context
    """
    csv_path = os.path.join(DATA_PATH, 'assessment_content.csv')
    
    try:
        with open(csv_path, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            all_examples = list(reader)
        
        # Extract base TP from mastery_level (e.g., "TP1_easy" -> "TP1")
        # This ensures backward compatibility with CSV that uses base TP codes
        base_tp = mastery_level
        if '_' in mastery_level:
            base_tp = mastery_level.split('_')[0]
        
        # Filter by mastery level (match base TP since CSV uses base codes)
        filtered_by_mastery = [
            ex for ex in all_examples 
            if ex.get('mastery_id') == base_tp
        ]
        
        # If we have matches for this mastery level, prioritize them
        if filtered_by_mastery:
            # Further filter by learning style if possible
            filtered_by_vark = [
                ex for ex in filtered_by_mastery 
                if ex.get('learning_type') == learning_style
            ]
            
            # Use VARK-matched examples if available, otherwise use mastery-matched
            candidates = filtered_by_vark if filtered_by_vark else filtered_by_mastery
        else:
            # Fallback: no exact mastery match, use any from dataset
            candidates = all_examples
        
        # Randomly sample examples to provide variety
        num_to_retrieve = min(num_examples, len(candidates))
        selected = random.sample(candidates, num_to_retrieve)
        
        # Format examples for prompt inclusion
        formatted_examples = []
        for ex in selected:
            formatted = {
                'question': ex.get('question_text', ''),
                'answer': ex.get('answer', ''),
                'context': ex.get('example', ''),
                'mastery_level': ex.get('mastery_id', ''),
                'learning_type': ex.get('learning_type', '')
            }
            
            # Include calculation steps if available
            if ex.get('calculation_step'):
                formatted['working'] = ex.get('calculation_step', '')
            
            formatted_examples.append(formatted)
        
        return formatted_examples
    
    except FileNotFoundError:
        print(f"Warning: Dataset not found at {csv_path}")
        return []
    except Exception as e:
        print(f"Error retrieving examples: {e}")
        return []