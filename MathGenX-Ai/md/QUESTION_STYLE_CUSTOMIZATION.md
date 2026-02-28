# Question Style Customization Guide

## Overview

You have **FULL FREEDOM** to customize question styles by editing JSON configuration files. No code changes needed!

## 3 Levels of Customization

### Level 1: Quick Style Changes (Easiest) ⭐
**File**: `knowledge_base/question_templates.json`

Just edit the `question_types` array to add new question styles:

```json
"TP3": {
  "question_types": [
    // ORIGINAL:
    "Add or subtract integers, fractions, or decimals",
    
    // ADD YOUR OWN STYLES:
    "Real-life shopping scenarios with money calculations",
    "Sports score problems with positive/negative points",
    "Temperature change word problems",
    "Cooking recipe measurement conversions",
    "Game score tracking with gains and losses"
  ],
  "complexity": "direct computation",
  "steps": "multi-step calculations"
}
```

The AI will **randomly choose** from these question types, giving you variety!

### Level 2: Add Constraints & Context (Medium)
**File**: `knowledge_base/question_templates.json`

Add custom constraints to control question generation:

```json
"TP4": {
  "question_types": [
    "Solve simple word problems with clear context",
    "Apply rational numbers in everyday situations"
  ],
  
  // ADD CUSTOM CONSTRAINTS:
  "preferred_contexts": ["Malaysian food", "local currency RM", "school scenarios"],
  "student_names": ["Ahmad", "Siti", "Kumar", "Mei Ling", "Fatimah"],
  "difficulty_hints": "Keep numbers under 100 for easier mental math",
  "avoid": ["overly abstract concepts", "unfamiliar cultural references"],
  "encourage": ["visual descriptions", "step-by-step thinking"]
}
```

### Level 3: Question Format Control (Advanced)
**File**: Input to API

Control whether questions are multiple-choice or subjective:

```python
# Multiple Choice (Default)
{
  'topic': 'Topic1',
  'mastery_level': 'TP3',
  'vark_style': 'Kinesthetic',
  'question_format': 'multiple_choice'  # 4 choices A-D
}

# Subjective (with working steps)
{
  'topic': 'Topic1',
  'mastery_level': 'TP5',
  'vark_style': 'Read/Write',
  'question_format': 'subjective'  # Show working steps with final answer
}
```

## Question Format Examples

### 1. Multiple Choice (Default)

**Input**:
```json
{
  "question_format": "multiple_choice"
}
```

**Output**:
```json
{
  "question_text": "Ahmad has RM50. He spends 1/4 on books and 0.3 on food. How much does he have left?",
  "choices": {
    "A": "RM22.50",
    "B": "RM25.00",
    "C": "RM27.50",
    "D": "RM30.00"
  },
  "answer_key": "A"
}
```

### 2. Subjective (with working steps)

**Input**:
```json
{
  "question_format": "subjective"
}
```

**Output**:
```json
{
  "question_text": "Ahmad bought 3/4 kg of rice and used 1/3 kg for cooking. Calculate how much rice is left.",
  "working_steps": {
    "step_1": "Find common denominator: LCM of 4 and 3 = 12",
    "step_2": "Convert fractions: 3/4 = 9/12, 1/3 = 4/12",
    "step_3": "Subtract: 9/12 - 4/12 = 5/12"
  },
  "final_answer": "5/12 kg",
  "answer_type": "numeric",
  "accepted_variations": ["5/12 kg", "5/12"],
  "total_marks": 4
}
```



## Practical Examples of Style Customization

### Example 1: Add More Variety to TP2

**Edit**: `knowledge_base/question_templates.json`

```json
"TP2": {
  "question_types": [
    // BEFORE: Only 4 types
    "Order a set of rational numbers",
    "Compare multiple rational numbers",
    "Represent rational numbers on a number line",
    "Explain properties of rational numbers",
    
    // AFTER: Add 10 more styles!
    "Match fractions to their decimal equivalents",
    "Find which student has the most/least based on fractions",
    "Arrange temperatures from coldest to warmest",
    "Compare prices using fractions and decimals",
    "Identify equivalent fractions in a visual diagram",
    "Real-world scenario: Who saved more money?",
    "Sports: Compare player scores with mixed numbers",
    "Cooking: Compare ingredient amounts across recipes",
    "Distance comparison using fractions of kilometers",
    "Time comparison using fractions of hours"
  ]
}
```

Now you get **14 different question styles** for TP2!

### Example 2: Malaysian Context Focus

```json
"TP4": {
  "question_types": [
    "Calculate petrol costs in RM with fractions",
    "School canteen food pricing problems",
    "Badminton tournament score calculations",
    "Roti canai pricing with discounts",
    "Temperature in Malaysian cities comparison",
    "Distance between Malaysian states",
    "Calculation of Zakat or donation amounts",
    "Malaysian currency exchange scenarios"
  ],
  
  "local_context": {
    "currency": "RM (Ringgit Malaysia)",
    "foods": ["nasi lemak", "roti canai", "teh tarik", "mee goreng"],
    "places": ["KL", "Penang", "Johor", "Sabah", "Sarawak"],
    "activities": ["badminton", "sepak takraw", "football", "hiking"]
  }
}
```

### Example 3: Story-Based Questions

```json
"TP5": {
  "question_types": [
    "Multi-chapter story problem: Ahmad's day at the market",
    "Adventure scenario: Hiking trip with multiple stops and calculations",
    "Business simulation: Running a small food stall",
    "Science project: Measuring and mixing chemicals (fractions)",
    "Construction problem: Building a model with precise measurements",
    "Recipe scaling: Adjusting a recipe for different number of people",
    "Budget planning: Student's monthly allowance management",
    "Travel problem: Road trip with multiple destinations and fuel"
  ],
  
  "story_elements": {
    "characters": "Use relatable Malaysian student names",
    "setting": "Familiar Malaysian locations and contexts",
    "plot": "Realistic scenarios students might encounter",
    "progression": "Build complexity across multiple steps"
  }
}
```

## Testing Your Customizations

### Test Script: `test_custom_styles.py`

```python
from rag_core.generator import generate_rag_question
import json

def test_all_formats():
    """Test different question formats"""
    
    formats = [
        'multiple_choice',
        'subjective'
    ]
    
    for fmt in formats:
        print(f"\n{'='*60}")
        print(f"Testing: {fmt}")
        print('='*60)
        
        result = generate_rag_question({
            'topic': 'Topic1',
            'mastery_level': 'TP3',
            'vark_style': 'Kinesthetic',
            'question_format': fmt
        })
        
        if result['status'] == 'success':
            print(json.dumps(result['data'], indent=2, ensure_ascii=False))
        else:
            print(f"Error: {result['message']}")

if __name__ == '__main__':
    test_all_formats()
```

Run:
```bash
python test_custom_styles.py
```

## API Usage with Custom Formats

### Laravel/PHP Example

```php
// Multiple Choice
$response = Http::post('http://localhost:5000/api/rag-generate', [
    'topic' => 'Topic1',
    'mastery_level' => 'TP3',
    'vark_style' => 'Kinesthetic',
    'question_format' => 'multiple_choice'
]);

// Subjective (with working steps)
$response = Http::post('http://localhost:5000/api/rag-generate', [
    'topic' => 'Topic1',
    'mastery_level' => 'TP5',
    'vark_style' => 'Read/Write',
    'question_format' => 'subjective'
]);
```

### React Example

```javascript
const [questionFormat, setQuestionFormat] = useState('multiple_choice');

const generateQuestion = async () => {
  const response = await fetch('http://localhost:5000/api/rag-generate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      topic: 'Topic1',
      mastery_level: 'TP3',
      vark_style: 'Kinesthetic',
      question_format: questionFormat  // 'multiple_choice', 'subjective'
    })
  });
  
  const data = await response.json();
  console.log(data);
};

// UI for format selection
<select onChange={(e) => setQuestionFormat(e.target.value)}>
  <option value="multiple_choice">Multiple Choice</option>
  <option value="subjective">Show Working</option>
</select>
```

## Advanced: Question Style Weights

Want some question types to appear more often? Add weights!

```json
"TP3": {
  "question_types": [
    {
      "type": "Basic calculation",
      "weight": 0.4,
      "description": "Simple add/subtract"
    },
    {
      "type": "Word problem",
      "weight": 0.3,
      "description": "Real-life context"
    },
    {
      "type": "Visual problem",
      "weight": 0.2,
      "description": "With diagrams"
    },
    {
      "type": "Puzzle",
      "weight": 0.1,
      "description": "Creative thinking"
    }
  ]
}
```

(Note: This requires additional code changes to implement weighted selection)

## Summary

**You have complete freedom to customize**:

1. ✅ **Question types** - Add as many styles as you want in `question_templates.json`
2. ✅ **Contexts** - Malaysian-focused, international, abstract, etc.
3. ✅ **Constraints** - Add custom rules for generation
4. ✅ **Formats** - Multiple choice, subjective short, subjective with steps
5. ✅ **Complexity** - Control through mastery levels TP1-TP6
6. ✅ **Learning styles** - Already supported through VARK

**No coding required** - just edit JSON files!

---

**Quick Start**: Edit `question_templates.json` → Add question types → Test with `python app.py`
