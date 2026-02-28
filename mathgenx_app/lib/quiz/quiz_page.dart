import 'package:flutter/material.dart';
import '../services/user_store.dart';
import '../services/quiz_service.dart';
import '../models/quiz_response.dart';
import 'quiz_session_page.dart';

// ── Data models ──────────────────────────────────────────────────────────────
class _Topic {
  final String id;
  final String title;
  final String description;
  final bool isLocked;
  const _Topic(this.id, this.title, this.description, {this.isLocked = false});
}

class _Subtopic {
  final String topicId;
  final String id;
  final String title;
  final String description;
  const _Subtopic(this.topicId, this.id, this.title, this.description);
}

const _topics = [
  _Topic('1', 'Rational Numbers', 'Integers, fractions, decimals & more'),
  _Topic('2', 'More Topics', 'Coming soon...', isLocked: true),
];

// Subtopics keyed by topicId.
const _subtopics = [
  _Subtopic('1', '1.1', 'Integers', 'Positive and negative whole numbers'),
  _Subtopic(
    '1',
    '1.2',
    'Basic Arithmetic with Integers',
    'Basic arithmetic operations involving integers',
  ),
  _Subtopic(
    '1',
    '1.3',
    'Positive & Negative Fractions',
    'Positive and negative fractions',
  ),
  _Subtopic(
    '1',
    '1.4',
    'Positive & Negative Decimals',
    'Positive and negative decimals',
  ),
  _Subtopic(
    '1',
    '1.5',
    'Rational Numbers',
    'Comprehensive concept of rational numbers',
  ),
];

// ── Page ──────────────────────────────────────────────────────────────────────
class QuizPage extends StatefulWidget {
  const QuizPage({super.key});

  @override
  State<QuizPage> createState() => _QuizPageState();
}

class _QuizPageState extends State<QuizPage> {
  // Backend values: 'english' | 'malay'
  String _language = 'english';
  // 'multiple_choice' only; 'subjective' is locked
  String _questionType = 'multiple_choice';
  _Topic? _selectedTopic;
  _Subtopic? _selectedSubtopic;

  bool get _canStart => _selectedSubtopic != null;

  void _startPractice() {
    if (!_canStart) return;

    final profile = UserStore.instance.profile;
    final payload = <String, dynamic>{
      'topic': int.parse(_selectedTopic!.id),
      'subtopic': _selectedSubtopic!.id,
      'question_format': _questionType,
      'language': _language.toLowerCase(),
      'level': profile?.level,
      'vark_style': profile?.learningResults?.vark,
    };

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _PayloadSheet(payload: payload, autoStart: true),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFF),
      appBar: AppBar(
        title: const Text(
          'MathQuest',
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
      body: Column(
        children: [
          // ── Scrollable content ──────────────────────────────────────────
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ── Language ────────────────────────────────────────────
                  _SectionHeader(
                    icon: Icons.language,
                    title: 'Language',
                    subtitle: 'Choose your preferred language',
                  ),
                  const SizedBox(height: 12),
                  _LanguageToggle(
                    selected: _language,
                    onChanged: (v) => setState(() => _language = v),
                  ),
                  const SizedBox(height: 24),

                  // ── Question Type ───────────────────────────────────────
                  _SectionHeader(
                    icon: Icons.description_outlined,
                    title: 'Question Type',
                    subtitle: 'Choose how you want to answer questions',
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _QuestionTypeCard(
                          icon: Icons.format_list_bulleted,
                          label: 'Multiple Choice',
                          description:
                              'Choose the correct answer from the options',
                          isSelected: _questionType == 'multiple_choice',
                          isLocked: false,
                          onTap: () =>
                              setState(() => _questionType = 'multiple_choice'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _QuestionTypeCard(
                          icon: Icons.edit_outlined,
                          label: 'Subjective',
                          description: 'Write a brief answer to the question',
                          isSelected: _questionType == 'subjective',
                          isLocked: true,
                          onTap: null,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),

                  // ── Topic ──────────────────────────────────────────────
                  _SectionHeader(
                    icon: Icons.category_outlined,
                    title: 'Select Topic',
                    subtitle: 'Choose the main topic you want to practice',
                  ),
                  const SizedBox(height: 12),
                  ..._topics.map(
                    (t) => _TopicTile(
                      topic: t,
                      isSelected: _selectedTopic?.id == t.id,
                      onTap: t.isLocked
                          ? null
                          : () => setState(() {
                              _selectedTopic = t;
                              // Reset subtopic when topic changes
                              _selectedSubtopic = null;
                            }),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // ── Subtopic (only shown after a topic is selected) ──────
                  if (_selectedTopic != null) ...[
                    _SectionHeader(
                      icon: Icons.menu_book_outlined,
                      title: 'Select Subtopic',
                      subtitle: 'Choose the topic you want to practice',
                    ),
                    const SizedBox(height: 12),
                    ..._subtopics
                        .where((s) => s.topicId == _selectedTopic!.id)
                        .map(
                          (s) => _SubtopicTile(
                            subtopic: s,
                            isSelected: _selectedSubtopic?.id == s.id,
                            onTap: () => setState(() => _selectedSubtopic = s),
                          ),
                        ),
                  ],
                  const SizedBox(height: 100), // breathing room above FAB
                ],
              ),
            ),
          ),
        ],
      ),

      // ── Sticky Start Practice button ──────────────────────────────────────
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Selection summary chip (shown when subtopic is picked)
              if (_selectedSubtopic != null) ...[
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 10,
                  ),
                  margin: const EdgeInsets.only(bottom: 10),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEEF2FF),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                      color: const Color(0xFF3B5BDB).withAlpha(80),
                    ),
                  ),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.check_circle,
                        color: Color(0xFF3B5BDB),
                        size: 16,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          '$_language  ·  Multiple Choice  ·  ${_selectedSubtopic!.id} ${_selectedSubtopic!.title}',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF3B5BDB),
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              SizedBox(
                width: double.infinity,
                height: 54,
                child: ElevatedButton.icon(
                  onPressed: _canStart ? _startPractice : null,
                  icon: const Icon(Icons.auto_awesome, size: 18),
                  label: const Text(
                    'Start Practice',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF3B5BDB),
                    foregroundColor: Colors.white,
                    disabledBackgroundColor: const Color(0xFFCBD5E1),
                    disabledForegroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                    elevation: 0,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Section header ────────────────────────────────────────────────────────────
class _SectionHeader extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  const _SectionHeader({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF3B5BDB), Color(0xFF7C3AED)],
            ),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, color: Colors.white, size: 18),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF0F172A),
                ),
              ),
              Text(
                subtitle,
                style: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

// ── Language toggle ───────────────────────────────────────────────────────────
class _LanguageToggle extends StatelessWidget {
  final String selected;
  final ValueChanged<String> onChanged;
  const _LanguageToggle({required this.selected, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        color: const Color(0xFFEEF2FF),
        borderRadius: BorderRadius.circular(12),
      ),
      padding: const EdgeInsets.all(4),
      child: Row(
        children: [
          Expanded(
            child: _LangChip(
              label: '🇬🇧  English',
              isSelected: selected == 'english',
              onTap: () => onChanged('english'),
            ),
          ),
          const SizedBox(width: 4),
          Expanded(
            child: _LangChip(
              label: '🇲🇾  Malay',
              isSelected: selected == 'malay',
              onTap: () => onChanged('malay'),
            ),
          ),
        ],
      ),
    );
  }
}

class _LangChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;
  const _LangChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? Colors.white : Colors.transparent,
          borderRadius: BorderRadius.circular(9),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: Colors.black.withAlpha(15),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: isSelected
                ? const Color(0xFF3B5BDB)
                : const Color(0xFF94A3B8),
          ),
        ),
      ),
    );
  }
}

// ── Question type card ────────────────────────────────────────────────────────
class _QuestionTypeCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final String description;
  final bool isSelected;
  final bool isLocked;
  final VoidCallback? onTap;

  const _QuestionTypeCard({
    required this.icon,
    required this.label,
    required this.description,
    required this.isSelected,
    required this.isLocked,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final Color borderColor = isLocked
        ? const Color(0xFFE2E8F0)
        : isSelected
        ? const Color(0xFF3B5BDB)
        : const Color(0xFFE2E8F0);
    final Color bgColor = isLocked
        ? const Color(0xFFFAFAFA)
        : isSelected
        ? const Color(0xFFEEF2FF)
        : Colors.white;

    return GestureDetector(
      onTap: isLocked ? null : onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: borderColor, width: isSelected ? 2 : 1.5),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  icon,
                  size: 22,
                  color: isLocked
                      ? const Color(0xFFCBD5E1)
                      : isSelected
                      ? const Color(0xFF3B5BDB)
                      : const Color(0xFF64748B),
                ),
                const Spacer(),
                if (isSelected && !isLocked)
                  Container(
                    width: 20,
                    height: 20,
                    decoration: const BoxDecoration(
                      color: Color(0xFF3B5BDB),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.check,
                      color: Colors.white,
                      size: 13,
                    ),
                  ),
                if (isLocked)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 7,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFDE68A),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: const Text(
                      '🔒 Coming Soon',
                      style: TextStyle(
                        fontSize: 9,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF92400E),
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              label,
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w700,
                color: isLocked
                    ? const Color(0xFFCBD5E1)
                    : const Color(0xFF0F172A),
              ),
            ),
            const SizedBox(height: 3),
            Text(
              description,
              style: TextStyle(
                fontSize: 11,
                color: isLocked
                    ? const Color(0xFFCBD5E1)
                    : const Color(0xFF64748B),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Topic tile ────────────────────────────────────────────────────────────────
class _TopicTile extends StatelessWidget {
  final _Topic topic;
  final bool isSelected;
  final VoidCallback? onTap;
  const _TopicTile({
    required this.topic,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final locked = topic.isLocked;
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: locked
              ? const Color(0xFFFAFAFA)
              : isSelected
              ? const Color(0xFFEEF2FF)
              : Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: locked
                ? const Color(0xFFE2E8F0)
                : isSelected
                ? const Color(0xFF3B5BDB)
                : const Color(0xFFE2E8F0),
            width: isSelected && !locked ? 2 : 1.5,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withAlpha(6),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            // ID badge
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: locked
                    ? const Color(0xFFE2E8F0)
                    : isSelected
                    ? const Color(0xFF3B5BDB)
                    : const Color(0xFFEEF2FF),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                topic.id,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: locked
                      ? const Color(0xFFCBD5E1)
                      : isSelected
                      ? Colors.white
                      : const Color(0xFF3B5BDB),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    topic.title,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: locked
                          ? const Color(0xFFCBD5E1)
                          : isSelected
                          ? const Color(0xFF3B5BDB)
                          : const Color(0xFF0F172A),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    topic.description,
                    style: TextStyle(
                      fontSize: 12,
                      color: locked
                          ? const Color(0xFFCBD5E1)
                          : const Color(0xFF94A3B8),
                    ),
                  ),
                ],
              ),
            ),
            if (locked)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFFFDE68A),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: const Text(
                  '🔒 Coming Soon',
                  style: TextStyle(
                    fontSize: 9,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF92400E),
                  ),
                ),
              )
            else if (isSelected)
              Container(
                width: 22,
                height: 22,
                decoration: const BoxDecoration(
                  color: Color(0xFF3B5BDB),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check, color: Colors.white, size: 14),
              ),
          ],
        ),
      ),
    );
  }
}

// ── Subtopic tile ─────────────────────────────────────────────────────────────
class _SubtopicTile extends StatelessWidget {
  final _Subtopic subtopic;
  final bool isSelected;
  final VoidCallback onTap;
  const _SubtopicTile({
    required this.subtopic,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFEEF2FF) : Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isSelected
                ? const Color(0xFF3B5BDB)
                : const Color(0xFFE2E8F0),
            width: isSelected ? 2 : 1.5,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withAlpha(6),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            // ID badge
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: isSelected
                    ? const Color(0xFF3B5BDB)
                    : const Color(0xFFEEF2FF),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                subtopic.id,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: isSelected ? Colors.white : const Color(0xFF3B5BDB),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    subtopic.title,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: isSelected
                          ? const Color(0xFF3B5BDB)
                          : const Color(0xFF0F172A),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subtopic.description,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF94A3B8),
                    ),
                  ),
                ],
              ),
            ),
            if (isSelected)
              Container(
                width: 22,
                height: 22,
                decoration: const BoxDecoration(
                  color: Color(0xFF3B5BDB),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check, color: Colors.white, size: 14),
              ),
          ],
        ),
      ),
    );
  }
}

// ── Payload preview bottom sheet ──────────────────────────────────────────────
// ── Step model (local to sheet) ───────────────────────────────────────────────
enum _SStep { pending, active, done, error }

class _SStepItem {
  final String label;
  final String? subtitle;
  _SStep state = _SStep.pending;
  _SStepItem(this.label, {this.subtitle});
}

// ── Bottom sheet: payload preview → inline generating ─────────────────────────
class _PayloadSheet extends StatefulWidget {
  final Map<String, dynamic> payload;
  final bool autoStart;
  const _PayloadSheet({required this.payload, this.autoStart = false});

  @override
  State<_PayloadSheet> createState() => _PayloadSheetState();
}

class _PayloadSheetState extends State<_PayloadSheet>
    with SingleTickerProviderStateMixin {
  static const _labels = {
    'topic': 'Topic',
    'subtopic': 'Subtopic',
    'question_format': 'Question Format',
    'language': 'Language',
    'level': 'Level',
    'vark_style': 'VARK Style',
  };

  bool _generating = false;
  String? _errorMessage;

  late AnimationController _progressCtrl;
  late Animation<double> _progressAnim;
  double _targetProgress = 0.0;

  final List<_SStepItem> _steps = [
    _SStepItem('Fetching your skill level...'),
    _SStepItem('Analyzing your learning style...'),
    _SStepItem(
      'Generating personalized questions...',
      subtitle: 'This may take a moment...',
    ),
    _SStepItem('Almost ready!'),
  ];

  @override
  void initState() {
    super.initState();
    _progressCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _progressAnim = Tween<double>(begin: 0, end: 0).animate(_progressCtrl);
    if (widget.autoStart) {
      _generating = true; // set before first build — payload view never renders
      WidgetsBinding.instance.addPostFrameCallback((_) => _startGenerating());
    }
  }

  @override
  void dispose() {
    _progressCtrl.dispose();
    super.dispose();
  }

  void _animateTo(double target) {
    final old = _targetProgress;
    _targetProgress = target;
    _progressAnim = Tween<double>(
      begin: old,
      end: target,
    ).animate(CurvedAnimation(parent: _progressCtrl, curve: Curves.easeInOut));
    _progressCtrl.forward(from: 0);
  }

  void _setActive(int i) {
    if (!mounted) return;
    setState(() => _steps[i].state = _SStep.active);
  }

  void _setDone(int i) {
    if (!mounted) return;
    setState(() => _steps[i].state = _SStep.done);
  }

  Future<void> _startGenerating() async {
    setState(() {
      _generating = true;
      _errorMessage = null;
    });

    QuizResponse? result;

    // Step 0 — fake 3s
    _setActive(0);
    await Future.delayed(const Duration(seconds: 3));
    _setDone(0);
    _animateTo(0.25);

    // Step 1 — fake 3s
    _setActive(1);
    await Future.delayed(const Duration(seconds: 3));
    _setDone(1);
    _animateTo(0.50);

    // Step 2 — real API
    _setActive(2);
    try {
      result = await QuizService.generateQuiz(widget.payload);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _steps[2].state = _SStep.error;
        _errorMessage = e.toString().replaceFirst('Exception: ', '');
      });
      return;
    }
    _setDone(2);
    _animateTo(0.75);

    // Step 3 — almost ready
    _setActive(3);
    await Future.delayed(const Duration(milliseconds: 800));
    _setDone(3);
    _animateTo(1.0);

    await Future.delayed(const Duration(milliseconds: 400));

    if (!mounted) return;
    Navigator.pop(context); // close sheet
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) =>
            QuizSessionPage(quizResponse: result!, quizPayload: widget.payload),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      child: AnimatedSwitcher(
        duration: const Duration(milliseconds: 350),
        child: _generating ? _buildGenerating() : _buildPayload(),
      ),
    );
  }

  // ── Payload preview ─────────────────────────────────────────────────────────
  Widget _buildPayload() {
    return Column(
      key: const ValueKey('payload'),
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _dragHandle(),
        // Title
        Row(
          children: [
            _iconBox(Icons.send_outlined),
            const SizedBox(width: 10),
            const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Payload Preview',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF0F172A),
                  ),
                ),
                Text(
                  'Data that will be sent to the backend',
                  style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                ),
              ],
            ),
          ],
        ),
        const SizedBox(height: 20),
        // Rows
        Container(
          decoration: BoxDecoration(
            color: const Color(0xFFF8FAFF),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            children: widget.payload.entries.toList().asMap().entries.map((e) {
              final isLast = e.key == widget.payload.length - 1;
              final key = e.value.key;
              final value = e.value.value;
              return Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 13,
                    ),
                    child: Row(
                      children: [
                        Text(
                          _labels[key] ?? key,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF64748B),
                          ),
                        ),
                        const Spacer(),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEEF2FF),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            '${value ?? '—'}',
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF3B5BDB),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (!isLast)
                    const Divider(
                      height: 1,
                      color: Color(0xFFE2E8F0),
                      indent: 16,
                    ),
                ],
              );
            }).toList(),
          ),
        ),
        const SizedBox(height: 20),
        // Proceed button
        SizedBox(
          width: double.infinity,
          height: 54,
          child: ElevatedButton.icon(
            onPressed: _startGenerating,
            icon: const Icon(Icons.play_arrow_rounded, size: 22),
            label: const Text(
              'Proceed to Quiz',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF3B5BDB),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
              elevation: 0,
            ),
          ),
        ),
      ],
    );
  }

  // ── Generating view ─────────────────────────────────────────────────────────
  Widget _buildGenerating() {
    return Column(
      key: const ValueKey('generating'),
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        _dragHandle(),
        // Icon
        Container(
          width: 64,
          height: 64,
          decoration: const BoxDecoration(
            color: Color(0xFFEEF2FF),
            shape: BoxShape.circle,
          ),
          child: const Icon(
            Icons.auto_awesome,
            color: Color(0xFF3B5BDB),
            size: 30,
          ),
        ),
        const SizedBox(height: 16),
        const Text(
          'Generating Your Questions',
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w800,
            color: Color(0xFF0F172A),
          ),
        ),
        const SizedBox(height: 6),
        const Text(
          'Please wait while we personalise your quiz...',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
        ),
        const SizedBox(height: 20),

        // Steps
        ..._steps.map((s) => _StepRow(step: s)),

        // Error
        if (_errorMessage != null) ...[
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFFFEDED),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Colors.redAccent.withAlpha(80)),
            ),
            child: Row(
              children: [
                const Icon(
                  Icons.error_outline,
                  color: Colors.redAccent,
                  size: 18,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    _errorMessage!,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Colors.redAccent,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 10),
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text(
              '← Go Back',
              style: TextStyle(color: Color(0xFF64748B)),
            ),
          ),
        ],

        const SizedBox(height: 16),
        // Progress bar
        AnimatedBuilder(
          animation: _progressAnim,
          builder: (ctx, _) => Column(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: LinearProgressIndicator(
                  value: _progressAnim.value,
                  minHeight: 8,
                  backgroundColor: const Color(0xFFE2E8F0),
                  valueColor: const AlwaysStoppedAnimation<Color>(
                    Color(0xFF3B5BDB),
                  ),
                ),
              ),
              const SizedBox(height: 6),
              Text(
                '${(_progressAnim.value * 100).toInt()}% Complete',
                style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _dragHandle() => Center(
    child: Container(
      width: 40,
      height: 4,
      margin: const EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        color: const Color(0xFFE2E8F0),
        borderRadius: BorderRadius.circular(2),
      ),
    ),
  );

  Widget _iconBox(IconData icon) => Container(
    padding: const EdgeInsets.all(8),
    decoration: BoxDecoration(
      gradient: const LinearGradient(
        colors: [Color(0xFF3B5BDB), Color(0xFF7C3AED)],
      ),
      borderRadius: BorderRadius.circular(10),
    ),
    child: Icon(icon, color: Colors.white, size: 18),
  );
}

// ── Step row inside the sheet ─────────────────────────────────────────────────
class _StepRow extends StatelessWidget {
  final _SStepItem step;
  const _StepRow({required this.step});

  @override
  Widget build(BuildContext context) {
    Color bg;
    Color border;
    Widget leading;

    switch (step.state) {
      case _SStep.done:
        bg = const Color(0xFFECFDF5);
        border = const Color(0xFF6EE7B7);
        leading = Container(
          width: 32,
          height: 32,
          decoration: const BoxDecoration(
            color: Color(0xFF10B981),
            shape: BoxShape.circle,
          ),
          child: const Icon(Icons.check, color: Colors.white, size: 18),
        );
      case _SStep.active:
        bg = const Color(0xFFEEF2FF);
        border = const Color(0xFF3B5BDB);
        leading = const SizedBox(
          width: 32,
          height: 32,
          child: CircularProgressIndicator(
            strokeWidth: 3,
            color: Color(0xFF3B5BDB),
          ),
        );
      case _SStep.error:
        bg = const Color(0xFFFFEDED);
        border = Colors.redAccent;
        leading = Container(
          width: 32,
          height: 32,
          decoration: const BoxDecoration(
            color: Colors.redAccent,
            shape: BoxShape.circle,
          ),
          child: const Icon(Icons.close, color: Colors.white, size: 18),
        );
      case _SStep.pending:
        bg = const Color(0xFFF8FAFF);
        border = const Color(0xFFE2E8F0);
        leading = Container(
          width: 32,
          height: 32,
          decoration: const BoxDecoration(
            color: Color(0xFFE2E8F0),
            shape: BoxShape.circle,
          ),
        );
    }

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: border),
        ),
        child: Row(
          children: [
            leading,
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  step.label,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: step.state == _SStep.pending
                        ? const Color(0xFF94A3B8)
                        : step.state == _SStep.error
                        ? Colors.redAccent
                        : const Color(0xFF0F172A),
                  ),
                ),
                if (step.subtitle != null && step.state == _SStep.active) ...[
                  const SizedBox(height: 2),
                  Text(
                    step.subtitle!,
                    style: const TextStyle(
                      fontSize: 11,
                      color: Color(0xFF94A3B8),
                    ),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}
