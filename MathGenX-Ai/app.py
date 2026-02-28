from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, field_validator
from typing import Optional, List, Dict
from rag_core.generator import generate_rag_question, generate_batch_questions
from rag_chatbot.chatbot_engine import process_chatbot_request
import os
from dotenv import load_dotenv
import json

load_dotenv()

app = FastAPI(
    title="MathGenX RAG API",
    description="Retrieval-Augmented Generation API for Mathematics Question Generation",
    version="1.0.0"
)

# Enable CORS for Laravel integration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, replace with your Laravel domain
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Level to Mastery Level Mapping ---
def get_mastery_levels_from_level(level: int) -> tuple:
    """
    Maps level (1-12) to TP and easy/hard distribution.
    
    Level mapping pattern:
    - Level 1: TP1 (7 easy + 3 hard)
    - Level 2: TP1 (3 easy + 7 hard)
    - Level 3: TP2 (7 easy + 3 hard)
    - Level 4: TP2 (3 easy + 7 hard)
    - Level 5: TP3 (7 easy + 3 hard)
    - Level 6: TP3 (3 easy + 7 hard)
    - Level 7: TP4 (7 easy + 3 hard)
    - Level 8: TP4 (3 easy + 7 hard)
    - Level 9: TP5 (7 easy + 3 hard)
    - Level 10: TP5 (3 easy + 7 hard)
    - Level 11: TP6 (7 easy + 3 hard)
    - Level 12: TP6 (3 easy + 7 hard)
    
    Args:
        level: Level number (1-12)
    
    Returns:
        Tuple of (tp_number, easy_count, hard_count)
        Example: (1, 7, 3) for level 1
    """
    if level < 1 or level > 12:
        raise ValueError(f"Level must be between 1 and 12, got {level}")
    
    # Calculate TP number (1-6)
    tp_number = ((level - 1) // 2) + 1
    
    # Determine distribution: odd levels = more easy, even levels = more hard
    is_odd = (level % 2) == 1
    
    if is_odd:
        # Odd levels: 7 easy + 3 hard
        easy_count = 7
        hard_count = 3
    else:
        # Even levels: 3 easy + 7 hard
        easy_count = 3
        hard_count = 7
    
    return (tp_number, easy_count, hard_count)

def get_mastery_levels_list(level: int) -> List[str]:
    """
    Gets the list of mastery levels for a given level.
    
    Args:
        level: Level number (1-12)
    
    Returns:
        List of mastery level strings (e.g., ["TP1_easy", "TP1_easy", ..., "TP1_hard", ...])
    """
    tp_number, easy_count, hard_count = get_mastery_levels_from_level(level)
    
    mastery_levels = []
    mastery_levels.extend([f"TP{tp_number}_easy"] * easy_count)
    mastery_levels.extend([f"TP{tp_number}_hard"] * hard_count)
    
    return mastery_levels

# --- Request/Response Models ---
class QuestionRequest(BaseModel):
    topic: str
    subtopic: Optional[str] = None
    level: int  # Level 1-12: 1=TP1_easy, 2=TP1_hard, 3=TP2_easy, ..., 12=TP6_hard
    question_format: Optional[str] = "multiple_choice"
    vark_style: str
    language: Optional[str] = "english"  # "english" or "malay"
    
    @field_validator('level')
    @classmethod
    def validate_level(cls, v):
        """Validate level is between 1 and 12"""
        if not isinstance(v, int) or v < 1 or v > 12:
            raise ValueError('level must be an integer between 1 and 12')
        return v
    
    @field_validator('question_format')
    @classmethod
    def validate_question_format(cls, v):
        """Validate question format"""
        if v not in ['multiple_choice', 'subjective']:
            raise ValueError('question_format must be "multiple_choice" or "subjective"')
        return v
    
    @field_validator('vark_style')
    @classmethod
    def validate_vark_style(cls, v):
        """Validate VARK style"""
        allowed = ['Visual', 'Read', 'Auditory', 'Kinesthetic']
        if v not in allowed:
            raise ValueError(f'vark_style must be one of: {", ".join(allowed)}')
        return v
    
    model_config = {
        "json_schema_extra": {
            "example": {
                "topic": "1",
                "subtopic": "1.1",
                "level": 5,
                "question_format": "multiple_choice",
                "vark_style": "Visual",
                "language": "english"
            }
        }
    }

class ChatbotRequest(BaseModel):
    practice_session_id: str  # Practice session identifier
    vark_style: str
    question: str
    answer: str
    user_prompt: str
    topic: str  # Topic identifier (e.g., '1' or 'Topic1')
    subtopic: Optional[str] = None  # Subtopic code (e.g., '1.1', '1.2')
    chat_history: Optional[List[Dict[str, str]]] = []  # Conversation history from Laravel
    
    @field_validator('vark_style')
    @classmethod
    def validate_vark_style(cls, v):
        """Validate VARK style"""
        allowed = ['Visual', 'Read', 'Auditory', 'Kinesthetic']
        if v not in allowed:
            raise ValueError(f'vark_style must be one of: {", ".join(allowed)}')
        return v
    
    @field_validator('chat_history')
    @classmethod
    def validate_chat_history(cls, v):
        """Validate chat_history format"""
        if v is None:
            return []
        if not isinstance(v, list):
            raise ValueError('chat_history must be a list')
        for msg in v:
            if not isinstance(msg, dict):
                raise ValueError('Each message in chat_history must be a dictionary')
            if 'role' not in msg or 'content' not in msg:
                raise ValueError('Each message must have "role" and "content" keys')
            if msg['role'] not in ['user', 'assistant']:
                raise ValueError('Message role must be "user" or "assistant"')
        return v
    
    model_config = {
        "json_schema_extra": {
            "example": {
                "practice_session_id": "abc-123",
                "vark_style": "Visual",
                "question": "Calculate: 5 + (-3)",
                "answer": "2",
                "user_prompt": "I don't understand how to solve this",
                "topic": "1",
                "subtopic": "1.1",
                "chat_history": [
                    {"role": "user", "content": "I don't understand"},
                    {"role": "assistant", "content": "Let's look at the number line..."}
                ]
            }
        }
    }

# --- API Endpoints ---
@app.get("/")
async def root():
    """Health check endpoint"""
    return {
        "status": "online",
        "service": "MathGenX RAG API",
        "version": "1.0.0",
        "docs": "/docs"
    }

@app.post("/api/rag-generate")
async def rag_generate_endpoint(request: QuestionRequest):
    """
    Generate personalized math questions using RAG.

    - **topic**: Topic identifier (e.g., '1' or 'Topic1' for Rational Numbers)
    - **subtopic**: Subtopic code (e.g., '1.1' for Integer, '1.2' for Integer Operations) - optional
    - **level**: Level number (1-12) that maps to TP with easy/hard distribution:
        * Level 1: TP1 (7 easy + 3 hard = 10 questions)
        * Level 2: TP1 (3 easy + 7 hard = 10 questions)
        * Level 3: TP2 (7 easy + 3 hard = 10 questions)
        * Level 4: TP2 (3 easy + 7 hard = 10 questions)
        * Level 5: TP3 (7 easy + 3 hard = 10 questions)
        * Level 6: TP3 (3 easy + 7 hard = 10 questions)
        * Level 7: TP4 (7 easy + 3 hard = 10 questions)
        * Level 8: TP4 (3 easy + 7 hard = 10 questions)
        * Level 9: TP5 (7 easy + 3 hard = 10 questions)
        * Level 10: TP5 (3 easy + 7 hard = 10 questions)
        * Level 11: TP6 (7 easy + 3 hard = 10 questions)
        * Level 12: TP6 (3 easy + 7 hard = 10 questions)
    - **question_format**: Output format (multiple_choice or subjective) - applies to ALL questions
    - **vark_style**: Learning style (Visual, Read, Auditory, Kinesthetic)
    - **language**: Language for the question (english or malay) - defaults to english
    
    **Question Distribution**:
    - Always generates 10 questions total
    - Odd levels (1,3,5,7,9,11): 7 easy + 3 hard
    - Even levels (2,4,6,8,10,12): 3 easy + 7 hard
    - All questions use the same question_format (no mixing)
    """
    try:
        # Convert Pydantic model to dict
        student_data = request.dict()
        
        # Normalize topic format: "1" -> "Topic1"
        topic = student_data.get('topic', '')
        if topic.isdigit():
            student_data['topic'] = f"Topic{topic}"
        
        # Get level and convert to mastery levels list
        level = student_data.get('level')
        mastery_levels = get_mastery_levels_list(level)
        num_questions = len(mastery_levels)  # Always 10 questions (7+3 or 3+7)
        
        # Get TP number for mastery level display
        tp_number, easy_count, hard_count = get_mastery_levels_from_level(level)
        mastery_level_display = f"TP{tp_number} ({easy_count} easy + {hard_count} hard)"
        
        # Generate questions
        result = generate_batch_questions(student_data, mastery_levels)
        
        if result['status'] == 'success':
            questions = []
            for idx, question_data in enumerate(result['questions'], 1):
                question_data.pop('raw_response', None)  # Remove raw_response to reduce payload
                question_data['id'] = idx  # Add sequential ID
                
                # Add subtopic_id if present
                if student_data.get('subtopic'):
                    question_data['subtopic_id'] = student_data['subtopic']
                
                questions.append(question_data)
            
            return {
                "success": True,
                "data": {
                    "questions": questions,
                    "metadata": {
                        "topic": student_data['topic'],
                        "subtopic": student_data.get('subtopic'),
                        "level": level,
                        "mastery_level": mastery_level_display,
                        "tp_number": tp_number,
                        "easy_count": easy_count,
                        "hard_count": hard_count,
                        "learning_style": student_data['vark_style'],
                        "question_format": student_data['question_format'],
                        "language": student_data.get('language', 'english'),
                        "total_generated": len(questions),
                        "total_requested": num_questions,
                        "failed_count": num_questions - len(questions),
                        "api_calls": 1  # 🔥 Only 1 API call!
                    }
                },
                "message": f"Successfully generated {len(questions)} out of {num_questions} questions in 1 API call"
            }
        else:
            raise HTTPException(
                status_code=500, 
                detail=f"Failed to generate questions: {result.get('message', 'Unknown error')}"
            )

    except ValueError as e:
        # Catch validation errors (e.g., exceeded max questions)
        raise HTTPException(status_code=400, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Internal server error: {str(e)}")

@app.post("/api/chatbot-help")
async def chatbot_help_endpoint(request: ChatbotRequest):
    """
    Chatbot endpoint to help students answer questions.
    Uses conversation history provided by Laravel.
    Returns structured JSON response similar to rag-generate endpoint.
    
    - **practice_session_id**: Practice session identifier (required)
    - **vark_style**: Learning style (Visual, Read, Auditory, Kinesthetic)
    - **question**: The current question the student is working on
    - **answer**: The correct answer (for reference, not given to student)
    - **user_prompt**: Student's request for help (language auto-detected)
    - **topic**: Topic identifier (e.g., '1' or 'Topic1')
    - **subtopic**: Subtopic code (e.g., '1.1', '1.2') - optional
    - **chat_history**: Conversation history from Laravel (optional, defaults to empty list)
      Format: [{"role": "user"|"assistant", "content": "..."}]
    
    **Features**:
    - Automatic language detection (responds in same language as user_prompt)
    - Adaptive responses (hints, explanations, or step-by-step based on user needs)
    - Conversation history managed by Laravel (sent with each request)
    - VARK-specific response styles
    - DSKP context integration for curriculum alignment
    """
    try:
        # Process chatbot request
        result = process_chatbot_request(
            practice_session_id=request.practice_session_id,
            vark_style=request.vark_style,
            question=request.question,
            answer=request.answer,
            user_prompt=request.user_prompt,
            topic=request.topic,
            subtopic=request.subtopic,
            chat_history=request.chat_history
        )
        
        if result['status'] == 'success':
            return {
                "success": True,
                "data": {
                    "response": result['response'],
                    "intent": result['intent'],
                    "metadata": result['metadata']
                },
                "message": "Response generated successfully"
            }
        else:
            raise HTTPException(
                status_code=500,
                detail=f"Failed to generate response: {result.get('message', 'Unknown error')}"
            )
    
    except ValueError as e:
        # Catch validation errors
        raise HTTPException(status_code=400, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Internal server error: {str(e)}")

@app.get("/health")
async def health_check():
    return {"status": "healthy"}

if __name__ == '__main__':
    # To run the FastAPI server:
    import uvicorn
    print("\n🚀 Starting MathGenX RAG API Server...")
    print("📖 API Documentation: http://localhost:8000/docs")
    print("🔗 Laravel endpoint: http://localhost:8000/api/rag-generate")
    uvicorn.run("app:app", host="0.0.0.0", port=8000, reload=True)
    
    # To run the RAG logic test directly without starting the server:
    # test_rag_generation()