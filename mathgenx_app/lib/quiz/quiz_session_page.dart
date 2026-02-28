import 'package:flutter/material.dart';
import '../models/quiz_response.dart';
import '../services/quiz_service.dart';
import 'quiz_result_page.dart';

class QuizSessionPage extends StatefulWidget {
  final QuizResponse quizResponse;
  final Map<String, dynamic> quizPayload;
  const QuizSessionPage({
    super.key,
    required this.quizResponse,
    required this.quizPayload,
  });

  @override
  State<QuizSessionPage> createState() => _QuizSessionPageState();
}

class _QuizSessionPageState extends State<QuizSessionPage> {
  int _currentIndex = 0;
  // Map from question index to the selected QuizChoice id
  final Map<int, int> _answers = {};
  late final DateTime _startTime;

  @override
  void initState() {
    super.initState();
    _startTime = DateTime.now();
  }

  int get _totalQuestions => widget.quizResponse.questions.length;
  int get _answeredCount => _answers.length;
  double get _progress =>
      _totalQuestions == 0 ? 0 : _answeredCount / _totalQuestions;

  void _onOptionSelected(int choiceId) {
    setState(() {
      _answers[_currentIndex] = choiceId;
    });
  }

  void _onPrevious() {
    if (_currentIndex > 0) {
      setState(() => _currentIndex--);
    }
  }

  void _onNext() {
    if (_currentIndex < _totalQuestions - 1) {
      setState(() => _currentIndex++);
    } else {
      _submitQuiz();
    }
  }

  bool _isSubmitting = false;

  Future<void> _submitQuiz() async {
    if (_isSubmitting) return;

    setState(() => _isSubmitting = true);

    try {
      final attemptPayload = <String, dynamic>{
        'topic_id': widget.quizPayload['topic']?.toString(),
        'subtopic_id': widget.quizPayload['subtopic']?.toString(),
        'question_format': widget.quizPayload['question_format'],
        'language': widget.quizPayload['language'],
        'level': widget.quizPayload['level'],
        'learning_style':
            widget.quizPayload['vark_style'], // Key used in quiz_page.dart
        'questions': widget.quizResponse.questions.map((q) => q.raw).toList(),
        'responses': widget.quizResponse.questions.asMap().entries.map((e) {
          return _answers[e.key];
        }).toList(),
        'started_at': _startTime.toIso8601String(),
      };

      final resultData = await QuizService.submitAttempt(attemptPayload);

      if (!mounted) return;

      // Navigate to QuizResultPage
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => QuizResultPage(resultData: resultData),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.quizResponse.questions.isEmpty) {
      return Scaffold(
        appBar: AppBar(title: const Text('MathQuest Quiz')),
        body: const Center(child: Text('No questions available.')),
      );
    }

    final question = widget.quizResponse.questions[_currentIndex];
    final selectedChoiceId = _answers[_currentIndex];
    final isLastQuestion = _currentIndex == _totalQuestions - 1;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFF),
      appBar: AppBar(
        title: const Text(
          'MathQuest Quiz',
          style: TextStyle(
            fontWeight: FontWeight.w800,
            fontSize: 18,
            color: Color(0xFF0F172A),
          ),
        ),
        centerTitle: true,
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(height: 1, color: const Color(0xFFE2E8F0)),
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            // ── Header: Progress and Status ─────────────────────────────
            Container(
              color: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Question ${_currentIndex + 1} of $_totalQuestions',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF64748B),
                        ),
                      ),
                      Text(
                        '$_answeredCount/$_totalQuestions answered',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF3B5BDB),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  // Progress Bar
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: LinearProgressIndicator(
                      value: _progress,
                      minHeight: 8,
                      backgroundColor: const Color(0xFFE2E8F0),
                      valueColor: const AlwaysStoppedAnimation<Color>(
                        Color(0xFF3B5BDB),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // ── Main Content: Question and Choices ──────────────────────
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Level Badge and Question Number badge
                    Row(
                      children: [
                        Container(
                          width: 36,
                          height: 36,
                          alignment: Alignment.center,
                          decoration: const BoxDecoration(
                            color: Color(0xFFEEF2FF),
                            shape: BoxShape.circle,
                          ),
                          child: Text(
                            '${question.number}',
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF3B5BDB),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        if (question.level != null)
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF3E8FF),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              question.level!,
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF7C3AED),
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // Question Text
                    Text(
                      question.questionText,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF0F172A),
                        height: 1.4,
                      ),
                    ),
                    const SizedBox(height: 32),

                    // Choices
                    ...question.choices.map((choice) {
                      final isSelected = selectedChoiceId == choice.id;
                      return GestureDetector(
                        onTap: () => _onOptionSelected(choice.id),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 200),
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 16,
                          ),
                          decoration: BoxDecoration(
                            color: isSelected
                                ? const Color(0xFFEEF2FF)
                                : Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isSelected
                                  ? const Color(0xFF3B5BDB)
                                  : const Color(0xFFE2E8F0),
                              width: isSelected ? 2 : 1,
                            ),
                            boxShadow: isSelected
                                ? null
                                : [
                                    BoxShadow(
                                      color: Colors.black.withAlpha(5),
                                      blurRadius: 4,
                                      offset: const Offset(0, 2),
                                    ),
                                  ],
                          ),
                          child: Row(
                            children: [
                              // Radio style circle
                              Container(
                                width: 22,
                                height: 22,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: isSelected
                                        ? const Color(0xFF3B5BDB)
                                        : const Color(0xFFCBD5E1),
                                    width: 2,
                                  ),
                                ),
                                child: isSelected
                                    ? Center(
                                        child: Container(
                                          width: 10,
                                          height: 10,
                                          decoration: const BoxDecoration(
                                            color: Color(0xFF3B5BDB),
                                            shape: BoxShape.circle,
                                          ),
                                        ),
                                      )
                                    : null,
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: Text(
                                  choice.answerText,
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: isSelected
                                        ? FontWeight.w600
                                        : FontWeight.w500,
                                    color: const Color(0xFF1E293B),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                  ],
                ),
              ),
            ),

            // ── Footer: Navigation Buttons ──────────────────────────────
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                border: Border(
                  top: BorderSide(
                    color: const Color(0xFFE2E8F0).withAlpha(150),
                  ),
                ),
              ),
              child: SafeArea(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
                  child: Row(
                    children: [
                      if (_currentIndex > 0)
                        Expanded(
                          flex: 1,
                          child: OutlinedButton(
                            onPressed: _onPrevious,
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              side: const BorderSide(color: Color(0xFFE2E8F0)),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                            ),
                            child: const Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  Icons.chevron_left,
                                  color: Color(0xFF64748B),
                                ),
                                SizedBox(width: 4),
                                Text(
                                  'Previous',
                                  style: TextStyle(
                                    color: Color(0xFF64748B),
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        )
                      else
                        const Spacer(),
                      const SizedBox(width: 12),
                      Expanded(
                        flex: 1,
                        child: ElevatedButton(
                          onPressed: isLastQuestion
                              ? (_answeredCount == _totalQuestions
                                    ? _submitQuiz
                                    : null) // Submit only if all answered
                              : _onNext, // Next is always enabled
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            backgroundColor: const Color(0xFF3B5BDB),
                            foregroundColor: Colors.white,
                            disabledBackgroundColor: const Color(0xFF94A3B8),
                            disabledForegroundColor: Colors.white,
                            elevation: 0,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                isLastQuestion ? 'Submit' : 'Next',
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              if (!isLastQuestion) ...[
                                const SizedBox(width: 4),
                                const Icon(Icons.chevron_right),
                              ],
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
