# API JSON Response Format - Documentation

## Overview

The API now returns **structured JSON responses** instead of raw text. This makes frontend integration much easier and provides clean, consistent data format.

## JSON Response Structure

### Success Response

```json
{
  "question_text": "Siti, Ben, and Amir are sharing a pizza...",
  "choices": {
    "A": "Siti",
    "B": "Ben",
    "C": "Amir",
    "D": "All ate the same amount"
  },
  "answer_key": "C",
  "topic_id": "Topic1",
  "mastery_level": "TP2",
  "learning_style": "Kinesthetic",
  "raw_response": "Topic: Topic1\nQuestion: Siti..."
}
```

### Field Descriptions

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `question_text` | string | The complete question text | "Siti, Ben, and Amir are sharing..." |
| `choices` | object | Answer choices A-D | `{"A": "text", "B": "text", ...}` |
| `answer_key` | string | Correct answer (A/B/C/D) | "C" |
| `topic_id` | string | Topic identifier | "Topic1" |
| `mastery_level` | string | DSKP mastery level | "TP1" through "TP6" |
| `learning_style` | string | VARK learning style | "Kinesthetic", "Visual", etc. |
| `raw_response` | string | Original AI response (debugging) | Full text from Gemini |

## API Endpoint

### Request

**URL**: `POST /api/rag-generate`

**Headers**:
```
Content-Type: application/json
```

**Body**:
```json
{
  "topic": "Topic1",
  "mastery_level": "TP3",
  "vark_style": "Kinesthetic"
}
```

### Response Examples

#### 1. Success (200 OK)

```json
{
  "question_text": "Ahmad walks to the market carrying 3/4 kg of sugar in a bag...",
  "choices": {
    "A": "1/2 kg",
    "B": "5/12 kg",
    "C": "7/12 kg",
    "D": "1 kg"
  },
  "answer_key": "B",
  "topic_id": "Topic1",
  "mastery_level": "TP3",
  "learning_style": "Kinesthetic",
  "raw_response": "..."
}
```

#### 2. Warning (200 OK - Parsing Issue)

If the AI response couldn't be parsed but was generated:

```json
{
  "status": "warning",
  "message": "Question generated but parsing failed: Expected 4 choices, found 3",
  "raw_question": "Topic: Topic1\nQuestion: ..."
}
```

#### 3. Error (400 Bad Request)

```json
{
  "status": "error",
  "message": "Missing required student data fields."
}
```

#### 4. Error (500 Internal Server Error)

```json
{
  "status": "error",
  "message": "Gemini API failed after 5 attempts: Server consistently unavailable"
}
```

## Frontend Integration Examples

### JavaScript (Fetch API)

```javascript
async function generateQuestion() {
  try {
    const response = await fetch('http://localhost:5000/api/rag-generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        topic: 'Topic1',
        mastery_level: 'TP3',
        vark_style: 'Kinesthetic'
      })
    });
    
    const data = await response.json();
    
    // Display question
    document.getElementById('question').textContent = data.question_text;
    
    // Display choices
    document.getElementById('choiceA').textContent = data.choices.A;
    document.getElementById('choiceB').textContent = data.choices.B;
    document.getElementById('choiceC').textContent = data.choices.C;
    document.getElementById('choiceD').textContent = data.choices.D;
    
    // Store answer for validation
    correctAnswer = data.answer_key;
    
    // Store metadata
    console.log('Topic:', data.topic_id);
    console.log('Mastery Level:', data.mastery_level);
    console.log('Learning Style:', data.learning_style);
    
  } catch (error) {
    console.error('Error generating question:', error);
  }
}
```

### React Example

```jsx
import React, { useState } from 'react';

function QuestionGenerator() {
  const [question, setQuestion] = useState(null);
  const [loading, setLoading] = useState(false);

  const generateQuestion = async () => {
    setLoading(true);
    try {
      const response = await fetch('http://localhost:5000/api/rag-generate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          topic: 'Topic1',
          mastery_level: 'TP3',
          vark_style: 'Kinesthetic'
        })
      });
      
      const data = await response.json();
      setQuestion(data);
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <button onClick={generateQuestion} disabled={loading}>
        {loading ? 'Generating...' : 'Generate Question'}
      </button>
      
      {question && (
        <div className="question-card">
          <h3>{question.question_text}</h3>
          <div className="choices">
            <button>A. {question.choices.A}</button>
            <button>B. {question.choices.B}</button>
            <button>C. {question.choices.C}</button>
            <button>D. {question.choices.D}</button>
          </div>
          <p className="metadata">
            Topic: {question.topic_id} | 
            Level: {question.mastery_level} | 
            Style: {question.learning_style}
          </p>
        </div>
      )}
    </div>
  );
}
```

### PHP/Laravel Example

```php
use Illuminate\Support\Facades\Http;

class QuestionController extends Controller
{
    public function generateQuestion(Request $request)
    {
        $response = Http::post('http://localhost:5000/api/rag-generate', [
            'topic' => $request->input('topic', 'Topic1'),
            'mastery_level' => $request->input('mastery_level', 'TP3'),
            'vark_style' => $request->input('vark_style', 'Kinesthetic')
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            
            // Save to database
            $question = Question::create([
                'question_text' => $data['question_text'],
                'choice_a' => $data['choices']['A'],
                'choice_b' => $data['choices']['B'],
                'choice_c' => $data['choices']['C'],
                'choice_d' => $data['choices']['D'],
                'answer_key' => $data['answer_key'],
                'topic_id' => $data['topic_id'],
                'mastery_level' => $data['mastery_level'],
                'learning_style' => $data['learning_style'],
            ]);
            
            return response()->json($question);
        }
        
        return response()->json(['error' => 'Failed to generate question'], 500);
    }
}
```

### Vue.js Example

```vue
<template>
  <div class="question-generator">
    <button @click="generateQuestion" :disabled="loading">
      {{ loading ? 'Generating...' : 'Generate Question' }}
    </button>
    
    <div v-if="question" class="question-card">
      <h3>{{ question.question_text }}</h3>
      <div class="choices">
        <label v-for="(text, key) in question.choices" :key="key">
          <input type="radio" :value="key" v-model="selectedAnswer">
          {{ key }}. {{ text }}
        </label>
      </div>
      <button @click="checkAnswer">Check Answer</button>
      <p v-if="showResult" :class="isCorrect ? 'correct' : 'incorrect'">
        {{ isCorrect ? 'Correct!' : `Wrong! The answer is ${question.answer_key}` }}
      </p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      question: null,
      loading: false,
      selectedAnswer: null,
      showResult: false,
      isCorrect: false
    };
  },
  methods: {
    async generateQuestion() {
      this.loading = true;
      this.showResult = false;
      this.selectedAnswer = null;
      
      try {
        const response = await fetch('http://localhost:5000/api/rag-generate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            topic: 'Topic1',
            mastery_level: 'TP3',
            vark_style: 'Kinesthetic'
          })
        });
        
        this.question = await response.json();
      } catch (error) {
        console.error('Error:', error);
      } finally {
        this.loading = false;
      }
    },
    checkAnswer() {
      this.isCorrect = this.selectedAnswer === this.question.answer_key;
      this.showResult = true;
    }
  }
};
</script>
```

## Database Storage

Suggested database schema for storing generated questions:

```sql
CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_text TEXT NOT NULL,
    choice_a VARCHAR(255) NOT NULL,
    choice_b VARCHAR(255) NOT NULL,
    choice_c VARCHAR(255) NOT NULL,
    choice_d VARCHAR(255) NOT NULL,
    answer_key CHAR(1) NOT NULL,
    topic_id VARCHAR(50) NOT NULL,
    mastery_level VARCHAR(10) NOT NULL,
    learning_style VARCHAR(50) NOT NULL,
    raw_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_topic (topic_id),
    INDEX idx_mastery (mastery_level),
    INDEX idx_style (learning_style)
);
```

## Testing

### Using cURL

```bash
curl -X POST http://localhost:5000/api/rag-generate \
  -H "Content-Type: application/json" \
  -d '{
    "topic": "Topic1",
    "mastery_level": "TP3",
    "vark_style": "Kinesthetic"
  }'
```

### Using Python Requests

```python
import requests
import json

response = requests.post(
    'http://localhost:5000/api/rag-generate',
    json={
        'topic': 'Topic1',
        'mastery_level': 'TP3',
        'vark_style': 'Kinesthetic'
    }
)

data = response.json()
print(json.dumps(data, indent=2))
```

### Using the Test Script

```bash
python test_json_response.py
```

## Error Handling Best Practices

### Frontend Error Handling

```javascript
async function generateQuestion() {
  try {
    const response = await fetch('http://localhost:5000/api/rag-generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        topic: 'Topic1',
        mastery_level: 'TP3',
        vark_style: 'Kinesthetic'
      })
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    // Check for warning status (parsing issues)
    if (data.status === 'warning') {
      console.warn('Parsing issue:', data.message);
      console.log('Raw question:', data.raw_question);
      // Show fallback UI or retry
      return;
    }
    
    // Success - use the data
    displayQuestion(data);
    
  } catch (error) {
    console.error('Error generating question:', error);
    showErrorMessage('Failed to generate question. Please try again.');
  }
}
```

## Response Time

- **Typical response time**: 2-5 seconds
- **With retry (503 errors)**: Up to 30 seconds
- **Recommendation**: Show loading spinner in frontend

## Rate Limiting

Consider implementing rate limiting on your Flask app:

```python
from flask_limiter import Limiter

limiter = Limiter(app, key_func=lambda: request.remote_addr)

@app.route('/api/rag-generate', methods=['POST'])
@limiter.limit("10 per minute")  # Max 10 requests per minute per IP
def rag_generate_endpoint():
    # ... existing code
```

## CORS Configuration

If your frontend is on a different domain:

```python
from flask_cors import CORS

app = Flask(__name__)
CORS(app, resources={r"/api/*": {"origins": "http://localhost:3000"}})
```

## Production Checklist

- [ ] Add authentication/API keys
- [ ] Implement rate limiting
- [ ] Configure CORS properly
- [ ] Set up logging for API calls
- [ ] Monitor API response times
- [ ] Cache frequently requested questions
- [ ] Set up error tracking (e.g., Sentry)
- [ ] Use environment variables for configuration
- [ ] Add API versioning (e.g., `/api/v1/rag-generate`)
- [ ] Implement request validation with proper error messages

---

**Last Updated**: November 19, 2025  
**API Version**: 2.0 (JSON Format)
