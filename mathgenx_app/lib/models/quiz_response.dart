class QuizChoice {
  final int id;
  final String label;
  final String answerText;
  final bool isCorrect;

  const QuizChoice({
    required this.id,
    required this.label,
    required this.answerText,
    required this.isCorrect,
  });

  factory QuizChoice.fromJson(Map<String, dynamic> json) {
    return QuizChoice(
      id: json['id'] as int,
      label: json['label']?.toString() ?? '',
      answerText: json['answer_text']?.toString() ?? '',
      isCorrect: json['is_correct'] == true,
    );
  }
}

class QuizQuestion {
  final int id;
  final int number; // 1-based index for UI
  final String questionText;
  final String? level;
  final String? learningStyle;
  final String? topicId;
  final String? imageUrl;
  final List<QuizChoice> choices;
  final Map<String, dynamic> raw;

  const QuizQuestion({
    required this.id,
    required this.number,
    required this.questionText,
    this.level,
    this.learningStyle,
    this.topicId,
    this.imageUrl,
    required this.choices,
    required this.raw,
  });

  factory QuizQuestion.fromJson(Map<String, dynamic> json, int index) {
    final rawChoices = json['choices'] as List<dynamic>? ?? [];
    return QuizQuestion(
      id: json['id'] as int? ?? index,
      number: index + 1,
      questionText: json['question_text']?.toString() ?? '',
      level: json['level']?.toString(),
      learningStyle: json['learning_style']?.toString(),
      topicId: json['topic_id']?.toString(),
      imageUrl: json['image_url']?.toString(),
      choices: rawChoices
          .map((c) => QuizChoice.fromJson(c as Map<String, dynamic>))
          .toList(),
      raw: json,
    );
  }
}

class QuizResponse {
  final List<QuizQuestion> questions;
  final int totalRequested;
  final String questionFormat;
  final String source; // 'api' | 'database_fallback'
  final int savedToDb;
  final String? warning;

  bool get isAiGenerated => source == 'api';

  const QuizResponse({
    required this.questions,
    required this.totalRequested,
    required this.questionFormat,
    required this.source,
    required this.savedToDb,
    this.warning,
  });

  factory QuizResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    final rawQuestions = data['questions'] as List<dynamic>? ?? [];
    return QuizResponse(
      questions: rawQuestions
          .asMap()
          .entries
          .map(
            (e) =>
                QuizQuestion.fromJson(e.value as Map<String, dynamic>, e.key),
          )
          .toList(),
      totalRequested:
          (data['total_requested'] as num?)?.toInt() ?? rawQuestions.length,
      questionFormat: data['question_format']?.toString() ?? 'multiple_choice',
      source: data['source']?.toString() ?? 'api',
      savedToDb: (data['saved_to_db'] as num?)?.toInt() ?? 0,
      warning: data['warning']?.toString(),
    );
  }
}
