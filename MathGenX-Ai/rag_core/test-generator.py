import requests
import os
from .retriever import get_dskp_context, get_vark_prompt
from dotenv import load_dotenv  # ← ADD THIS

load_dotenv()

# MegaLLM API details
API_URL = "https://ai.megallm.io/v1/chat/completions"
API_KEY = os.getenv('MEGALLM_API_KEY', 'YOUR_PLACEHOLDER_API_KEY') # Use placeholder if not set

def generate_rag_question(student_data):
    """
    Performs the RAG process: Retrieval, Augmentation, and Generation.
    
    Args:
        student_data (dict): Contains 'topic', 'level', 'vark_style', and 'math_capabilities'.
    
    Returns:
        dict: The LLM's full response or an error message.
    """
    topic = student_data.get('topic', 'Fractions')
    capability = student_data.get('math_capabilities', 'Proficient')
    vark_style = student_data.get('vark_style', 'Read/Write')

    # --- R: Retrieval ---
    dskp_context, complexity = get_dskp_context(topic, capability)
    vark_context = get_vark_prompt(vark_style)

    # --- A: Augmentation (Building the Prompt) ---
    base_instructions = f""" 
        You are a highly skilled math teacher writing multiple-choice questions for Form 1 students in Malaysia.
        Your task is to generate ONE personalized question that STRICTLY FOLLOWS ALL constraints provided in the [CONTEXTUAL GUIDANCE] below.

        [CONTEXTUAL GUIDANCE START]
        {dskp_context.strip()}
        {vark_context.strip()}
        [CONTEXTUAL GUIDANCE END]

        Final Output Requirements:
        - Topic: {topic}
        - Write ONE question only.
        - The question must be at the '{complexity}' difficulty level.
        - Include 4 answer choices labeled A-D.
        - Include the correct answer on the next line: "Answer: X" (where X is A, B, C, or D).
        - Use simple, clear language suitable for 13-year-old students.
        - If fractions are used, write them like "3/4". Do not use LaTeX or special characters.

        Example Format:
        Question: Ali and Abu share money in the ratio 3:2. If their total amount of money is RM50, how much money does Ali have?
        A. RM20
        B. RM25
        C. RM30
        D. RM35
        Answer: C

        Now, generate the question.
    """
    
    # --- G: Generation (API Call) ---
    
    headers = {
        "Authorization": f"Bearer {API_KEY}",
        "Content-Type": "application/json"
    }
    
    payload = {
        "model": "gpt-3.5-turbo",
        "messages": [
            {
                "role": "user",
                "content": base_instructions
            }
        ],
        "max_tokens": 150 # Increased max_tokens slightly for the full MC question + answer
    }
    
    try:
        print("--- Sending Augmented Prompt to MegaLLM... ---")
        # In a real application, you would implement exponential backoff here.
        response = requests.post(API_URL, headers=headers, json=payload, timeout=30)
        response.raise_for_status() # Raise exception for bad status codes
        
        result = response.json()
        
        # Extract the generated content
        generated_content = result.get('choices', [{}])[0].get('message', {}).get('content', 'Error: No content generated.')
        
        return {
            "status": "success",
            "question": generated_content,
            "dskp_context_used": dskp_context.strip(),
            "vark_context_used": vark_context.strip(),
            "full_prompt": base_instructions # Useful for debugging
        }
    
    except requests.exceptions.RequestException as e:
        return {
            "status": "error",
            "message": f"API request failed: {e}",
            "full_prompt": base_instructions
        }