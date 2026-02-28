"""
Chatbot Engine for RAG-based student assistance.
Provides adaptive help based on VARK learning style and user intent.
"""
import os
import json
from typing import Dict, Optional, Tuple, List
from dotenv import load_dotenv

from rag_core.retriever import get_dskp_context, get_vark_prompt
from rag_core.llm_provider import get_provider

load_dotenv()

# Define paths
KB_PATH = os.path.join(os.path.dirname(__file__), '..', 'knowledge_base')


def load_json(filename: str) -> dict:
    """Load a JSON file from the knowledge_base directory."""
    filepath = os.path.join(KB_PATH, filename)
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            return json.load(f)
    except FileNotFoundError:
        print(f"Error: Knowledge file not found at {filepath}")
        return {}


def detect_intent(user_prompt: str, answer_templates: dict) -> str:
    """
    Detect user intent from prompt (hint, explanation, step_by_step).
    
    Args:
        user_prompt: The user's prompt text
        answer_templates: Answer templates dictionary with intent_detection keywords
    
    Returns:
        'hint', 'explanation', or 'step_by_step'
    """
    user_lower = user_prompt.lower()
    intent_keywords = answer_templates.get('intent_detection', {})
    
    # Check for step-by-step keywords first (most specific)
    step_keywords = intent_keywords.get('step_by_step_keywords', [])
    for keyword in step_keywords:
        if keyword.lower() in user_lower:
            return 'step_by_step'
    
    # Check for explanation keywords
    explanation_keywords = intent_keywords.get('explanation_keywords', [])
    for keyword in explanation_keywords:
        if keyword.lower() in user_lower:
            return 'explanation'
    
    # Check for hint keywords
    hint_keywords = intent_keywords.get('hint_keywords', [])
    for keyword in hint_keywords:
        if keyword.lower() in user_lower:
            return 'hint'
    
    # Default to hint if user seems stuck
    if any(word in user_lower for word in ['stuck', 'help', 'don\'t know', 'tak tahu', 'buntu']):
        return 'hint'
    
    # Default to explanation
    return 'explanation'


def get_answer_template(vark_style: str, intent: str, answer_templates: dict) -> str:
    """
    Get the appropriate answer template based on VARK style and intent.
    
    Args:
        vark_style: VARK learning style (Visual, Read, Auditory, Kinesthetic)
        intent: User intent (hint, explanation, step_by_step)
        answer_templates: Answer templates dictionary
    
    Returns:
        Template instruction string
    """
    vark_data = answer_templates.get(vark_style, answer_templates.get('Read', {}))
    
    if intent == 'hint':
        return vark_data.get('hint_style', 'Provide a helpful hint.')
    elif intent == 'step_by_step':
        return vark_data.get('step_by_step_style', 'Provide step-by-step guidance.')
    else:  # explanation
        return vark_data.get('explanation_style', 'Provide an explanation.')


def build_chatbot_prompt(
    question: str,
    answer: str,
    user_prompt: str,
    vark_style: str,
    topic: str,
    subtopic: Optional[str],
    dskp_context: str,
    answer_template: str,
    conversation_history: list,
    answer_templates: dict
) -> str:
    """
    Build the RAG prompt for the chatbot.
    
    Args:
        question: The current question
        answer: The correct answer
        user_prompt: User's request for help
        vark_style: VARK learning style
        topic: Topic identifier
        subtopic: Optional subtopic code
        dskp_context: DSKP context string
        answer_template: VARK-specific answer template
        conversation_history: Previous conversation messages
    
    Returns:
        Complete prompt string
    """
    # Build conversation history context
    history_text = ""
    if conversation_history:
        history_text = "\n\nPrevious conversation:\n"
        for msg in conversation_history:
            role = msg.get('role', 'user')
            content = msg.get('content', '')
            if role == 'user':
                history_text += f"Student: {content}\n"
            else:
                history_text += f"Assistant: {content}\n"
    
    # Build the prompt - let LLM decide when to give answer
    prompt = f"""You are a helpful math tutor assisting a Form 1 student in Malaysia.

{dskp_context.strip()}

Current Question:
{question}

Correct Answer (for your reference):
{answer}

Student's Learning Style ({vark_style}):
{answer_template}

Student's Request:
{user_prompt}
{history_text}

IMPORTANT DECISION RULES:
1. LANGUAGE DETECTION: Analyze the conversation history and the student's current request to determine what language they are using (English or Bahasa Malaysia/Malay). Respond in the SAME language that the student is using. If they use English, respond in English. If they use Malay, respond in Bahasa Malaysia.

2. If the student explicitly asks for the answer (e.g., "give me the answer", "what's the answer", "I want the answer", "just tell me", "show me the solution", "what is it", "nak jawapan", "beri jawapan", etc.), you MUST:
   - Provide the answer clearly (e.g., "The answer is {answer}" or "Answer: {answer}")
   - Explain WHY it's correct using the student's learning style ({vark_style})
   - Keep explanation brief (20-30 words)

3. ANSWER MATCHING: When providing the answer, you MUST use the EXACT wording from the answer choices in the question. Even if you are conversing in Malay/Bahasa Malaysia, the answer itself must match the answer choice exactly as it appears in the question. For example, if the answer choice is "Morning", say "Morning" (not "Pagi"), even if your explanation is in Malay. The answer text must match the question's answer choices exactly.

4. If the student asks for help, hints, or explanations WITHOUT explicitly requesting the answer, guide them without giving the answer directly.

5. If the student asks questions UNRELATED to the math problem (e.g., "what's the weather?", "how are you?", "what time is it?", "tell me a joke", etc.), politely redirect them:
   - Acknowledge briefly (e.g., "I'm here to help with math!")
   - Redirect to the current question: "Let's focus on this problem: {question}"
   - Offer help: "Would you like help solving it?"
   - Keep it friendly and concise (20-30 words)

6. Use the student's learning style ({vark_style}) to tailor your response: {answer_template}

7. Be encouraging and supportive.

8. Remember previous messages in the conversation for context.

9. CRITICAL: Keep your response VERY CONCISE - aim for 20-40 words maximum. Be direct and to the point.

10. FORMATTING: Do NOT use special formatting characters like ** (bold), / (slashes), @, #, ^, or $. Use plain text only. Write numbers and text normally without markdown or special symbols.

Analyze the student's request, detect their language from the conversation, and respond appropriately in their language (20-40 words only, plain text only):"""
    
    return prompt


def process_chatbot_request(
    practice_session_id: str,
    vark_style: str,
    question: str,
    answer: str,
    user_prompt: str,
    topic: str,
    subtopic: Optional[str] = None,
    chat_history: Optional[List[Dict[str, str]]] = None
) -> Dict:
    """
    Process a chatbot request and generate a helpful response.
    
    Args:
        practice_session_id: Practice session identifier
        vark_style: VARK learning style
        question: The current question
        answer: The correct answer
        user_prompt: User's request for help
        topic: Topic identifier
        subtopic: Optional subtopic code
        chat_history: Optional conversation history from Laravel
    
    Returns:
        Dictionary with status, response data, and metadata
    """
    # Load answer templates
    answer_templates = load_json('answer_templates.json')
    if not answer_templates:
        return {
            "status": "error",
            "message": "Failed to load answer templates"
        }
    
    # Use provided chat_history or default to empty list
    history = chat_history if chat_history is not None else []
    
    # Normalize chat_history format - ensure "assistant" role (not "chatbot")
    normalized_history = []
    for msg in history:
        role = msg.get('role', 'user')
        # Convert "chatbot" to "assistant" if needed
        if role == 'chatbot':
            role = 'assistant'
        normalized_history.append({
            'role': role,
            'content': msg.get('content', '')
        })
    
    # Detect intent
    intent = detect_intent(user_prompt, answer_templates)
    
    # Get VARK-specific answer template
    answer_template = get_answer_template(vark_style, intent, answer_templates)
    
    # Normalize topic format
    normalized_topic = topic
    if topic.isdigit():
        normalized_topic = f"Topic{topic}"
    
    # Get DSKP context (always retrieve)
    dskp_context, _ = get_dskp_context(normalized_topic, 'TP3', subtopic)  # Default to TP3, can be enhanced
    
    # Build prompt
    prompt = build_chatbot_prompt(
        question=question,
        answer=answer,
        user_prompt=user_prompt,
        vark_style=vark_style,
        topic=normalized_topic,
        subtopic=subtopic,
        dskp_context=dskp_context,
        answer_template=answer_template,
        conversation_history=normalized_history,
        answer_templates=answer_templates
    )
    
    # Get LLM provider
    provider = get_provider()
    if provider is None:
        return {
            "status": "error",
            "message": "LLM provider not initialized. Check API key environment variable."
        }
    
    # Generate response
    try:
        result = provider.generate_content(
            prompt=prompt,
            temperature=0.7,
            max_tokens=1024  # Reduced for concise 30-50 word responses
        )
        
        if result.get('error'):
            return {
                "status": "error",
                "message": f"LLM API error: {result['error']}"
            }
        
        response_text = result.get('text', '').strip()
        
        if not response_text:
            return {
                "status": "error",
                "message": "LLM returned empty response"
            }
        
        # Note: Conversation history is managed by Laravel, not saved here
        
        return {
            "status": "success",
            "response": response_text,
            "intent": intent,
            "metadata": {
                "practice_session_id": practice_session_id,
                "topic": normalized_topic,
                "subtopic": subtopic,
                "vark_style": vark_style
            }
        }
    
    except Exception as e:
        return {
            "status": "error",
            "message": f"Error processing request: {str(e)}"
        }

