# Dynamic Question Template System

## Overview
The question generation system now uses a **dynamic JSON-based template system** that allows you to customize question generation based on:
- **Topic** (Fractions, Integers, Ratio, Algebra)
- **Difficulty Level** (Beginner, Proficient, Advanced)
- **VARK Learning Style** (Kinesthetic, Visual, Auditory, Read/Write)

## File Structure

```
knowledge_base/
├── question_templates.json    # Main template configuration (NEW)
├── dskp_math_form1.json       # DSKP standards (existing)
└── vark_templates.json        # VARK learning styles (existing)
```

## How It Works

### 1. Template Selection
When generating a question, the system:
1. Reads `question_templates.json` for topic-specific instructions
2. Reads `dskp_math_form1.json` for curriculum standards
3. Reads `vark_templates.json` for learning style constraints
4. Combines all contexts into a dynamic prompt sent to Gemini

### 2. Template Structure

```json
{
  "topics": {
    "TopicName": {
      "general_instructions": "Overall guidance for the topic",
      "notation_guide": "How to write mathematical notation",
      "common_contexts": ["context1", "context2"],
      
      "difficulty_specific": {
        "Beginner": {
          "question_types": ["Type 1", "Type 2"],
          "custom_constraint": "value"
        },
        "Proficient": { ... },
        "Advanced": { ... }
      },
      
      "example_questions": {
        "Beginner": "Example question...",
        "Proficient": "Example question...",
        "Advanced": "Example question..."
      }
    }
  }
}
```

## Customization Guide

### Adding a New Topic

1. Open `knowledge_base/question_templates.json`
2. Add a new entry under `"topics"`:

```json
"Geometry": {
  "general_instructions": "Focus on shapes, angles, and spatial reasoning.",
  "notation_guide": "Use standard geometry notation (e.g., angle ABC, triangle PQR).",
  "common_contexts": ["measuring rooms", "drawing shapes", "building structures"],
  
  "difficulty_specific": {
    "Beginner": {
      "question_types": [
        "Identifying basic shapes",
        "Counting sides and corners",
        "Measuring angles with a protractor"
      ],
      "angle_range": "0° to 180°",
      "steps": "single-step"
    },
    "Proficient": {
      "question_types": [
        "Calculating perimeter and area",
        "Working with properties of triangles",
        "Angle relationships in polygons"
      ],
      "complexity": "multi-shape problems",
      "steps": "multi-step"
    },
    "Advanced": {
      "question_types": [
        "Volume and surface area",
        "Complex composite shapes",
        "Geometric proofs and reasoning"
      ],
      "complexity": "3D shapes and complex calculations",
      "steps": "multi-step with reasoning"
    }
  },
  
  "example_questions": {
    "Beginner": "A rectangle has a length of 8 cm and width of 5 cm. What is its perimeter?",
    "Proficient": "A triangle has sides of 5 cm, 7 cm, and 10 cm. What type of triangle is it?",
    "Advanced": "A rectangular prism has dimensions 4 cm × 5 cm × 6 cm. What is its volume?"
  }
}
```

3. Add corresponding DSKP standards in `dskp_math_form1.json`:

```json
"Geometry": {
  "Beginner": {
    "focus": "Identifying and naming 2D shapes. Understanding basic properties.",
    "complexity": "EASY"
  },
  "Proficient": {
    "focus": "Calculating perimeter and area of rectangles and triangles.",
    "complexity": "MEDIUM"
  },
  "Advanced": {
    "focus": "Working with 3D shapes, volume, and surface area.",
    "complexity": "HARD"
  }
}
```

### Modifying Existing Topics

#### Example: Adjust Fractions for More Advanced Operations

In `question_templates.json`, modify the "Advanced" section:

```json
"Fractions": {
  "difficulty_specific": {
    "Advanced": {
      "question_types": [
        "Multiplication of fractions",
        "Division of fractions",
        "Complex word problems combining multiple operations",
        "Fraction problems with algebraic elements",
        "Converting between mixed numbers and improper fractions",  // NEW
        "Word problems requiring fraction of a fraction"            // NEW
      ],
      "max_denominator": "no limit",
      "allow_mixed_numbers": true,                                  // NEW
      "steps": "multi-step with complex reasoning"
    }
  }
}
```

### Customizing Question Types by Difficulty

You can specify different question types for each difficulty level:

```json
"difficulty_specific": {
  "Beginner": {
    "question_types": [
      "Type A - Simple",
      "Type B - Visual"
    ],
    "max_value": 10,
    "steps": "single-step"
  },
  "Proficient": {
    "question_types": [
      "Type C - Multi-step",
      "Type D - Word problems"
    ],
    "max_value": 100,
    "steps": "multi-step"
  }
}
```

### Adding Custom Constraints

You can add any custom key-value pairs as constraints:

```json
"Beginner": {
  "question_types": [...],
  "max_denominator": 8,
  "use_malaysian_currency": true,
  "common_student_names": ["Ahmad", "Siti", "Kumar", "Mei Ling"],
  "avoid_decimals": true,
  "steps": "single-step"
}
```

These constraints will be included in the prompt sent to the AI.

## Testing Your Changes

### Test a Specific Configuration

Edit `app.py` to test your changes:

```python
test_input = {
    'topic': 'Geometry',           # Your new topic
    'level': 'Form 1',
    'vark_style': 'Visual',        # Visual, Kinesthetic, Auditory, Read/Write
    'math_capabilities': 'Proficient'  # Beginner, Proficient, Advanced
}
```

Run:
```bash
python app.py
```

### Test Multiple Scenarios

Use the comprehensive test script:

```bash
python test_dynamic_templates.py
```

This will test all topics with different difficulty levels and VARK styles.

## Examples of Generated Questions

### Fractions (Beginner, Kinesthetic)
```
Question: Chef Mei is baking a cake. First, she carefully pours 2/5 cup of flour 
into a mixing bowl. Then, she adds in another 1/5 cup of flour. What is the total 
amount of flour in the mixing bowl?
A. 1/5 cup
B. 2/5 cup
C. 3/5 cup
D. 4/5 cup
Answer: C
```

### Integers (Proficient, Auditory)
```
Question: During a weather report, the announcer stated that the temperature 
was 15°C in the morning. By afternoon, they announced it had increased by 8°C. 
What was the afternoon temperature?
A. 7°C
B. 23°C
C. -7°C
D. 120°C
Answer: B
```

### Ratio (Advanced, Visual)
```
Question: A diagram shows three containers with heights in the ratio 2:3:5. 
If the shortest container is 12 cm tall, what is the height of the tallest container?
A. 18 cm
B. 24 cm
C. 30 cm
D. 36 cm
Answer: C
```

## Tips for Writing Good Templates

1. **Be Specific**: The more specific your question types, the better the AI can generate appropriate questions
2. **Include Examples**: Example questions help the AI understand the expected format
3. **Use Constraints**: Add constraints like "max_denominator", "range", "steps" to guide generation
4. **Context Matters**: Provide common contexts relevant to Malaysian students
5. **Test Thoroughly**: Always test new templates with different VARK styles
6. **Maintain Consistency**: Keep the same structure across all topics for easier maintenance

## Troubleshooting

### Question not following template
- Check that your JSON is valid (use a JSON validator)
- Ensure all required fields are present
- Verify the topic name matches exactly (case-sensitive)

### Questions too easy/hard
- Adjust the `question_types` array
- Modify constraints like `max_value`, `range`, or `complexity`
- Update the `example_questions` to show the desired difficulty

### Wrong VARK style
- Check `vark_templates.json` for the style definition
- Ensure the `instruction` field is clear and specific
- Test with different VARK styles to see variations

## Next Steps

1. **Add More Topics**: Extend to other Form 1 topics (Decimals, Percentages, Data Handling)
2. **Fine-tune Existing Topics**: Adjust question types based on actual student performance
3. **Create Topic Combinations**: Add templates for questions that combine multiple topics
4. **Expand VARK Styles**: Create more nuanced VARK variations in `vark_templates.json`

---

**Note**: After making changes to JSON files, restart your Flask application to reload the templates.
