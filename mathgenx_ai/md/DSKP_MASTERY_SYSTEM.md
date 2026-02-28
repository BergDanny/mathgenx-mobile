# DSKP Mastery Level System - Implementation Guide

## Overview

The system has been updated to use **6 DSKP Mastery Levels (TP1-TP6)** instead of the simple Beginner/Proficient/Advanced classification. This aligns with Malaysia's actual DSKP (Dokumen Standard Kurikulum dan Pentaksiran) standards.

## File Structure

```
knowledge_base/
├── dskp_F1_T1_mastery.json       # DSKP mastery levels for Form 1, Topic 1 (NEW)
├── question_templates.json        # Question templates mapped to TP1-TP6 (UPDATED)
├── vark_templates.json           # VARK learning styles (unchanged)
└── dskp_math_form1.json          # Old file (can be removed)
```

## Mastery Levels Explained

### Topic 1: Rational Numbers (Integers, Fractions, Decimals)

| Level | Code | Complexity | Description | Question Focus |
|-------|------|------------|-------------|----------------|
| **TP1** | Tahap Penguasaan 1 | VERY_EASY | Basic knowledge of integers, fractions, decimals | Identify, name, compare |
| **TP2** | Tahap Penguasaan 2 | EASY | Understanding rational numbers | Order, represent, explain |
| **TP3** | Tahap Penguasaan 3 | MEDIUM | Apply basic arithmetic operations | Add, subtract, multiply, divide |
| **TP4** | Tahap Penguasaan 4 | MEDIUM_HARD | Solve simple routine problems | Everyday contexts, 2-3 steps |
| **TP5** | Tahap Penguasaan 5 | HARD | Solve complex routine problems | Multi-step, requires planning |
| **TP6** | Tahap Penguasaan 6 | VERY_HARD | Solve non-routine problems | Creative thinking, reasoning |

## Changes Made

### 1. **New File: `dskp_F1_T1_mastery.json`**

Contains official DSKP mastery standards translated to English:

```json
{
  "topic_name": "Rational Numbers (Integers, Fractions, Decimals)",
  "topic_code": "Topic1",
  "mastery_levels": {
    "TP1": {
      "name": "Mastery Level 1",
      "complexity": "VERY_EASY",
      "description": "Demonstrate basic knowledge...",
      "cognitive_level": "Knowledge/Understanding"
    },
    // ... TP2 through TP6
  }
}
```

### 2. **Updated: `question_templates.json`**

Changed from topic names (Fractions, Integers) to **Topic1** structure with mastery levels:

```json
{
  "topics": {
    "Topic1": {
      "topic_name": "Rational Numbers (Integers, Fractions, Decimals)",
      "mastery_specific": {
        "TP1": { "question_types": [...] },
        "TP2": { "question_types": [...] },
        // ... through TP6
      }
    }
  }
}
```

### 3. **Updated: `retriever.py`**

- `get_dskp_context(topic, mastery_level)` - reads from `dskp_F1_T1_mastery.json`
- `get_question_template(topic, mastery_level)` - maps to TP1-TP6 templates

### 4. **Updated: `generator.py`**

Changed parameters:
- `math_capabilities` → `mastery_level`
- `'Fractions'` → `'Topic1'`

### 5. **Updated: `app.py`**

Test input now uses:
```python
{
    'topic': 'Topic1',
    'mastery_level': 'TP2',  # TP1-TP6
    'vark_style': 'Kinesthetic'
}
```

## How to Use

### For Testing

```python
# Test with different mastery levels
test_input = {
    'topic': 'Topic1',
    'mastery_level': 'TP1',  # Change to TP1, TP2, ..., TP6
    'vark_style': 'Visual',
    'level': 'Form 1'
}

result = generate_rag_question(test_input)
```

### For API Integration (Laravel)

Your Laravel backend should send:

```json
{
  "topic": "Topic1",
  "mastery_level": "TP3",
  "vark_style": "Kinesthetic"
}
```

API endpoint: `POST /api/rag-generate`

## Example Generated Questions by Mastery Level

### TP1 (VERY_EASY - Recognition)
```
Question: Which of the following is an integer?
A. 3/4
B. 0.5
C. -7
D. 1.25
Answer: C
```

### TP2 (EASY - Understanding)
```
Question: Four students stand on a number line at -2, 1/2, -0.75, and 1.
Arrange them from left to right.
A. Ali (-2), Cindy (-0.75), Bala (1/2), David (1)
B. ...
Answer: A
```

### TP3 (MEDIUM - Operations)
```
Question: Calculate: 3/4 + 1/2 - 0.25
A. 0.5
B. 1.0
C. 1.25
D. 1.5
Answer: B
```

### TP4 (MEDIUM_HARD - Simple Problems)
```
Question: Siti bought 3/4 kg of flour. She used 1/3 kg for a cake.
How much flour is left?
A. 5/12 kg
B. 7/12 kg
C. ...
Answer: B
```

### TP5 (HARD - Complex Problems)
```
Question: Ahmad deposited RM150, withdrew RM75, deposited RM50,
then spent 1/4 of his balance. How much does he have?
A. RM93.75
B. ...
Answer: A
```

### TP6 (VERY_HARD - Non-routine)
```
Question: A tank is 2/3 full. After using 30 liters, it's 1/2 full.
What is the tank's capacity?
A. 90 liters
B. 120 liters
C. 180 liters
D. 240 liters
Answer: C
```

## Adding More Topics

When you add Topic 2, Topic 3, etc.:

### Step 1: Create DSKP file

`knowledge_base/dskp_F1_T2_mastery.json`

```json
{
  "topic_name": "Your Topic Name",
  "topic_code": "Topic2",
  "mastery_levels": {
    "TP1": { ... },
    // ... through TP6
  }
}
```

### Step 2: Add to question_templates.json

```json
{
  "topics": {
    "Topic1": { ... },
    "Topic2": {
      "topic_name": "Your Topic Name",
      "mastery_specific": {
        "TP1": { "question_types": [...] },
        // ... through TP6
      }
    }
  }
}
```

### Step 3: Update retriever.py

Modify `get_dskp_context()` to load the appropriate file based on topic:

```python
def get_dskp_context(topic, mastery_level):
    # Map topic to file
    topic_files = {
        'Topic1': 'dskp_F1_T1_mastery.json',
        'Topic2': 'dskp_F1_T2_mastery.json',
        # ... add more
    }
    
    filename = topic_files.get(topic, 'dskp_F1_T1_mastery.json')
    dskp_data = load_json(filename)
    # ... rest of function
```

## Testing All Mastery Levels

```bash
# Test all 6 mastery levels for Topic 1
for level in TP1 TP2 TP3 TP4 TP5 TP6; do
  # Edit app.py to use $level
  python app.py
done
```

Or create a comprehensive test script:

```python
# test_mastery_levels.py
for tp in ['TP1', 'TP2', 'TP3', 'TP4', 'TP5', 'TP6']:
    for vark in ['Kinesthetic', 'Visual', 'Auditory', 'Read/Write']:
        test_input = {
            'topic': 'Topic1',
            'mastery_level': tp,
            'vark_style': vark
        }
        result = generate_rag_question(test_input)
        # Display result
```

## Cognitive Progression

The 6 levels follow Bloom's Taxonomy:

1. **TP1**: Knowledge (Remember/Recognize)
2. **TP2**: Understanding (Explain/Describe)
3. **TP3**: Application (Use/Apply)
4. **TP4**: Application in Context (Solve familiar)
5. **TP5**: Higher Application (Solve complex)
6. **TP6**: Analysis/Synthesis (Create/Evaluate)

## Benefits of This System

✅ **Aligned with DSKP** - Matches Malaysian curriculum standards  
✅ **More granular** - 6 levels instead of 3 for better differentiation  
✅ **Cognitive clarity** - Each level has clear cognitive expectations  
✅ **Scalable** - Easy to add Topic 2, Topic 3, etc.  
✅ **Flexible** - Works with all VARK styles  
✅ **Traceable** - Clear mapping from student ability → mastery level → question type  

## Migration Notes

### Old System
```python
{
  'topic': 'Fractions',
  'math_capabilities': 'Beginner'  # or Proficient, Advanced
}
```

### New System
```python
{
  'topic': 'Topic1',
  'mastery_level': 'TP2'  # TP1 through TP6
}
```

### Mapping Old to New (Approximate)
- Beginner → TP1 or TP2
- Proficient → TP3 or TP4
- Advanced → TP5 or TP6

---

**Last Updated**: November 19, 2025  
**System Version**: 2.0 (DSKP Mastery Levels)
