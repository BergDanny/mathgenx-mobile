class QuizAttempt {
  final String id;
  final String topicId;
  final String subtopicId;
  final String questionFormat;
  final String masteryLevel;
  final String learningStyle;
  final int totalQuestions;
  final int correctAnswers;
  final int incorrectAnswers;
  final double scorePercentage;
  final int marksObtained;
  final int expGained;
  final int totalMarks;
  final DateTime startedAt;
  final DateTime completedAt;
  final int timeSpentSeconds;

  const QuizAttempt({
    required this.id,
    required this.topicId,
    required this.subtopicId,
    required this.questionFormat,
    required this.masteryLevel,
    required this.learningStyle,
    required this.totalQuestions,
    required this.correctAnswers,
    required this.incorrectAnswers,
    required this.scorePercentage,
    required this.marksObtained,
    required this.expGained,
    required this.totalMarks,
    required this.startedAt,
    required this.completedAt,
    required this.timeSpentSeconds,
  });

  factory QuizAttempt.fromJson(Map<String, dynamic> json) => QuizAttempt(
    id: json['id'] as String,
    topicId: json['topic_id'] as String,
    subtopicId: json['subtopic_id'] as String,
    questionFormat: json['question_format'] as String,
    masteryLevel: json['mastery_level'] as String,
    learningStyle: json['learning_style'] as String,
    totalQuestions: (json['total_questions'] as num).toInt(),
    correctAnswers: (json['correct_answers'] as num).toInt(),
    incorrectAnswers: (json['incorrect_answers'] as num).toInt(),
    scorePercentage: double.parse(json['score_percentage'].toString()),
    marksObtained: (json['marks_obtained'] as num).toInt(),
    expGained: (json['exp_gained'] as num).toInt(),
    totalMarks: (json['total_marks'] as num).toInt(),
    startedAt: DateTime.parse(json['started_at'] as String),
    completedAt: DateTime.parse(json['completed_at'] as String),
    timeSpentSeconds: (json['time_spent_seconds'] as num).toInt(),
  );
}
