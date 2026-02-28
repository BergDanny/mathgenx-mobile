import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import '../services/user_store.dart';
import '../services/attempts_service.dart';
import '../models/user_profile.dart';
import '../models/quiz_attempt.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  UserProfile? _profile;
  List<QuizAttempt>? _attempts;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final cachedProfile = UserStore.instance.profile;
      final profileFuture = cachedProfile != null
          ? Future.value(cachedProfile)
          : UserStore.instance.fetchProfile();
      final attemptsFuture = AttemptsService.fetchAttempts();

      final results = await Future.wait([profileFuture, attemptsFuture]);

      if (mounted) {
        setState(() {
          _profile = results[0] as UserProfile;
          _attempts = results[1] as List<QuizAttempt>;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFF),
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(color: Color(0xFF3B5BDB)),
            )
          : _error != null
          ? _buildError()
          : _buildContent(),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 48, color: Colors.redAccent),
            const SizedBox(height: 12),
            Text(
              _error!.replaceFirst('Exception: ', ''),
              textAlign: TextAlign.center,
              style: const TextStyle(color: Color(0xFF64748B)),
            ),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: () {
                setState(() {
                  _loading = true;
                  _error = null;
                });
                _loadData();
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF3B5BDB),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              child: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContent() {
    final p = _profile!;
    final attempts = _attempts ?? [];
    final firstName = p.name.split(' ').first;

    return RefreshIndicator(
      color: const Color(0xFF3B5BDB),
      onRefresh: () async {
        UserStore.instance.clear();
        try {
          final results = await Future.wait([
            UserStore.instance.fetchProfile(),
            AttemptsService.fetchAttempts(),
          ]);
          if (mounted) {
            setState(() {
              _profile = results[0] as UserProfile;
              _attempts = results[1] as List<QuizAttempt>;
              _error = null;
            });
          }
        } catch (e) {
          if (mounted) {
            setState(() => _error = e.toString());
          }
        }
      },
      child: CustomScrollView(
        slivers: [
          // ── Gradient App Bar ──────────────────────────────────────────────
          SliverAppBar(
            expandedHeight: 160,
            pinned: true,
            backgroundColor: const Color(0xFF3B5BDB),
            surfaceTintColor: Colors.transparent,
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFF3B5BDB), Color(0xFF7C3AED)],
                  ),
                ),
                padding: const EdgeInsets.fromLTRB(20, 60, 20, 16),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.end,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Welcome back,',
                            style: TextStyle(
                              color: Colors.white.withAlpha(200),
                              fontSize: 14,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            firstName,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 26,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Ready to solve some math problems today?',
                            style: TextStyle(
                              color: Colors.white.withAlpha(180),
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 10,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withAlpha(30),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: Colors.white.withAlpha(60),
                          width: 1,
                        ),
                      ),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(
                            Icons.emoji_events,
                            color: Colors.amber,
                            size: 20,
                          ),
                          const SizedBox(height: 2),
                          const Text(
                            'Level',
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: 10,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          Text(
                            '${p.level}',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 20,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),

          // ── Cards ─────────────────────────────────────────────────────────
          SliverPadding(
            padding: const EdgeInsets.all(16),
            sliver: SliverList(
              delegate: SliverChildListDelegate([
                _ExpProgressCard(expInfo: p.expInfo),
                const SizedBox(height: 16),
                if (p.learningResults != null) ...[
                  _LearningStyleCard(results: p.learningResults!),
                  const SizedBox(height: 16),
                ],
                _StudentProgressCard(attempts: attempts),
                const SizedBox(height: 16),
                _RecentActivityCard(attempts: attempts),
                const SizedBox(height: 16),
                _StatisticsCard(attempts: attempts, expInfo: p.expInfo),
                const SizedBox(height: 24),
              ]),
            ),
          ),
        ],
      ),
    );
  }
}

// ── EXP Progress Card ─────────────────────────────────────────────────────────
class _ExpProgressCard extends StatelessWidget {
  final ExpInfo expInfo;
  const _ExpProgressCard({required this.expInfo});

  @override
  Widget build(BuildContext context) {
    double progress = 0.0;
    if (expInfo.expNeededForNextLevel > 0) {
      progress = (expInfo.expInCurrentLevel / expInfo.expNeededForNextLevel)
          .clamp(0.0, 1.0);
    }
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Experience Points',
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF0F172A),
                ),
              ),
              Text(
                '${expInfo.expInCurrentLevel} / ${expInfo.expNeededForNextLevel} EXP',
                style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
              ),
            ],
          ),
          const SizedBox(height: 10),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 10,
              backgroundColor: const Color(0xFFEEF2FF),
              valueColor: const AlwaysStoppedAnimation<Color>(
                Color(0xFF3B5BDB),
              ),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Next level: ${expInfo.expNeededForNextLevel - expInfo.expInCurrentLevel} EXP away',
            style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
          ),
        ],
      ),
    );
  }
}

// ── Learning Style Card ───────────────────────────────────────────────────────
class _LearningStyleCard extends StatelessWidget {
  final LearningResults results;
  const _LearningStyleCard({required this.results});

  static const _varkLabels = {
    'V': 'Visual',
    'A': 'Auditory',
    'R': 'Read/Write',
    'K': 'Kinesthetic',
  };

  @override
  Widget build(BuildContext context) {
    final scores = {
      'V': results.varkScores.scoreV,
      'A': results.varkScores.scoreA,
      'R': results.varkScores.scoreR,
      'K': results.varkScores.scoreK,
    };
    final maxScore = scores.values
        .reduce((a, b) => a > b ? a : b)
        .toDouble()
        .clamp(1.0, double.infinity);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              _iconBox(Icons.headphones),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '✦ ${results.vark.toUpperCase()}',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF3B5BDB),
                        letterSpacing: 0.5,
                      ),
                    ),
                    const Text(
                      'Your dominant learning style',
                      style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFF3B5BDB),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Text(
                  'Personalized',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ...scores.entries.map(
            (e) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Row(
                children: [
                  SizedBox(
                    width: 20,
                    child: Text(
                      e.key,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF3B5BDB),
                      ),
                    ),
                  ),
                  const SizedBox(width: 4),
                  SizedBox(
                    width: 80,
                    child: Text(
                      _varkLabels[e.key]!,
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ),
                  Expanded(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(6),
                      child: LinearProgressIndicator(
                        value: e.value / maxScore,
                        minHeight: 8,
                        backgroundColor: const Color(0xFFEEF2FF),
                        valueColor: const AlwaysStoppedAnimation<Color>(
                          Color(0xFF3B5BDB),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '${e.value}',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF0F172A),
                    ),
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: () {
                // Navigate to drill assessment natively or re-trigger flow
                // As this is a placeholder context for the prompt
              },
              icon: const Icon(Icons.sync, size: 18),
              label: const Text(
                'Retake Assessment',
                style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
              ),
              style: OutlinedButton.styleFrom(
                foregroundColor: const Color(0xFF3B5BDB),
                backgroundColor: const Color(0xFFEEF2FF),
                side: const BorderSide(color: Color(0xFFE2E8F0)),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
                elevation: 0,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Student Progress Card ─────────────────────────────────────────────────────
class _StudentProgressCard extends StatelessWidget {
  final List<QuizAttempt> attempts;
  const _StudentProgressCard({required this.attempts});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF3B5BDB), Color(0xFF7C3AED)],
                  ),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.trending_up,
                  color: Colors.white,
                  size: 18,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
                    Text(
                      '✦ STUDENT PROGRESS',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF3B5BDB),
                        letterSpacing: 0.5,
                      ),
                    ),
                    Text(
                      'Track your accuracy improvement up to 50 latest quiz attempts.',
                      style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // Body
          attempts.isEmpty
              ? _emptyState(
                  Icons.bar_chart,
                  'No quiz attempts yet',
                  'Start practicing to see your progress!',
                )
              : _buildChart(context),
        ],
      ),
    );
  }

  Widget _buildChart(BuildContext context) {
    final spots = attempts.asMap().entries.map((e) {
      return FlSpot(e.key.toDouble(), e.value.scorePercentage);
    }).toList();

    final maxY = attempts.map((a) => a.scorePercentage).reduce(math.max);

    return Column(
      children: [
        SizedBox(
          height: 220,
          child: Padding(
            padding: const EdgeInsets.only(right: 8),
            child: LineChart(
              LineChartData(
                minX: -0.5,
                maxX: (attempts.length - 1).toDouble() + 0.5,
                minY: 0,
                maxY: math.max(maxY + 15, 30),
                gridData: FlGridData(
                  show: true,
                  drawVerticalLine: false,
                  getDrawingHorizontalLine: (value) =>
                      FlLine(color: const Color(0xFFE2E8F0), strokeWidth: 1),
                ),
                borderData: FlBorderData(
                  show: true,
                  border: const Border(
                    left: BorderSide(color: Color(0xFFE2E8F0)),
                    bottom: BorderSide(color: Color(0xFFE2E8F0)),
                  ),
                ),
                titlesData: FlTitlesData(
                  topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  leftTitles: AxisTitles(
                    axisNameWidget: const RotatedBox(
                      quarterTurns: 3,
                      child: Text(
                        'Accuracy (%)',
                        style: TextStyle(
                          fontSize: 10,
                          color: Color(0xFF94A3B8),
                        ),
                      ),
                    ),
                    sideTitles: SideTitles(
                      showTitles: true,
                      reservedSize: 36,
                      interval: 20,
                      getTitlesWidget: (v, _) => Text(
                        v.toInt().toString(),
                        style: const TextStyle(
                          fontSize: 10,
                          color: Color(0xFF94A3B8),
                        ),
                      ),
                    ),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      reservedSize: 28,
                      getTitlesWidget: (v, _) {
                        final idx = v.toInt();
                        if (idx < 0 || idx >= attempts.length) {
                          return const SizedBox.shrink();
                        }
                        return Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Text(
                            'Attempt ${idx + 1}',
                            style: const TextStyle(
                              fontSize: 9,
                              color: Color(0xFF94A3B8),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),
                lineBarsData: [
                  LineChartBarData(
                    spots: spots,
                    isCurved: spots.length > 2,
                    color: const Color(0xFF3B5BDB),
                    barWidth: 2,
                    dotData: FlDotData(
                      show: true,
                      getDotPainter: (spot, pct, bar, idx) =>
                          FlDotCirclePainter(
                            radius: 5,
                            color: const Color(0xFF3B5BDB),
                            strokeWidth: 2,
                            strokeColor: Colors.white,
                          ),
                    ),
                    belowBarData: BarAreaData(
                      show: true,
                      color: const Color(0xFF3B5BDB).withAlpha(25),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(height: 12),
        // Total attempts badge
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            border: Border.all(
              color: const Color(0xFF3B5BDB).withAlpha(80),
              width: 1.5,
              style: BorderStyle.solid,
            ),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Row(
            children: [
              const Text(
                'TOTAL ATTEMPTS:',
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF374151),
                  letterSpacing: 0.3,
                ),
              ),
              const Spacer(),
              Container(
                width: 8,
                height: 8,
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  color: Color(0xFF3B5BDB),
                ),
              ),
              const SizedBox(width: 6),
              Text(
                '${attempts.length}',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF3B5BDB),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

// ── Recent Activity Card ──────────────────────────────────────────────────────
class _RecentActivityCard extends StatelessWidget {
  final List<QuizAttempt> attempts;
  const _RecentActivityCard({required this.attempts});

  @override
  Widget build(BuildContext context) {
    // Show the 5 most recent
    final recent = attempts.take(5).toList();

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF3B5BDB), Color(0xFF7C3AED)],
                  ),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.history_edu,
                  color: Colors.white,
                  size: 18,
                ),
              ),
              const SizedBox(width: 10),
              const Text(
                '✦ RECENT ACTIVITY',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF3B5BDB),
                  letterSpacing: 0.5,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          recent.isEmpty
              ? _emptyState(
                  Icons.inbox_outlined,
                  'No recent quiz attempts',
                  'Start practicing to see your activity here!',
                )
              : Column(
                  children: recent.map((a) => _AttemptRow(attempt: a)).toList(),
                ),
        ],
      ),
    );
  }
}

class _AttemptRow extends StatelessWidget {
  final QuizAttempt attempt;
  const _AttemptRow({required this.attempt});

  String _timeAgo(DateTime dt) {
    final diff = DateTime.now().toUtc().difference(dt.toUtc());
    if (diff.inMinutes < 1) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    return '${diff.inDays}d ago';
  }

  Color _scoreColor(double pct) {
    if (pct >= 70) return const Color(0xFF059669);
    if (pct >= 40) return const Color(0xFFD97706);
    return const Color(0xFFDC2626);
  }

  Color _scoreBg(double pct) {
    if (pct >= 70) return const Color(0xFFD1FAE5);
    if (pct >= 40) return const Color(0xFFFEF3C7);
    return const Color(0xFFFFEDED);
  }

  @override
  Widget build(BuildContext context) {
    final pct = attempt.scorePercentage;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.centerLeft,
            end: Alignment.centerRight,
            colors: [Color(0xFFF0F4FF), Color(0xFFFFF0F8)],
          ),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Topic ${attempt.topicId} - ${attempt.subtopicId}',
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF0F172A),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    attempt.subtopicId,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF64748B),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      border: Border.all(color: const Color(0xFFD97706)),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.bolt,
                          size: 12,
                          color: Color(0xFFD97706),
                        ),
                        const SizedBox(width: 2),
                        Text(
                          '${attempt.expGained} EXP',
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFFD97706),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: _scoreBg(pct),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    '${pct.toStringAsFixed(1)}%',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: _scoreColor(pct),
                    ),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  _timeAgo(attempt.completedAt),
                  style: const TextStyle(
                    fontSize: 11,
                    color: Color(0xFF94A3B8),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ── Statistics Card ───────────────────────────────────────────────────────────
class _StatisticsCard extends StatelessWidget {
  final List<QuizAttempt> attempts;
  final ExpInfo expInfo;
  const _StatisticsCard({required this.attempts, required this.expInfo});

  @override
  Widget build(BuildContext context) {
    final totalQuizzes = attempts.length;
    final totalExp = attempts.isEmpty
        ? expInfo
              .exp // fall back to profile EXP if no attempts yet
        : attempts.fold<int>(0, (s, a) => s + a.expGained);
    final avgScore = attempts.isEmpty
        ? 0.0
        : attempts.fold<double>(0, (s, a) => s + a.scorePercentage) /
              attempts.length;
    final questionsAnswered = attempts.fold<int>(
      0,
      (s, a) => s + a.totalQuestions,
    );

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _cardDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF3B5BDB), Color(0xFF7C3AED)],
                  ),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.bar_chart,
                  color: Colors.white,
                  size: 18,
                ),
              ),
              const SizedBox(width: 10),
              const Text(
                '✦ STATISTICS',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF3B5BDB),
                  letterSpacing: 0.5,
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          _StatTile(
            label: 'Total Quizzes',
            value: '$totalQuizzes',
            icon: Icons.description_outlined,
            bg: const Color(0xFFDEEAFF),
            iconBg: const Color(0xFFBDD7FF),
            valueColor: const Color(0xFF1D4ED8),
            iconColor: const Color(0xFF1D4ED8),
          ),
          const SizedBox(height: 10),
          _StatTile(
            label: 'Total EXP',
            value: '$totalExp',
            icon: Icons.bolt,
            bg: const Color(0xFFFEF3C7),
            iconBg: const Color(0xFFFDE68A),
            valueColor: const Color(0xFFB45309),
            iconColor: const Color(0xFFB45309),
          ),
          const SizedBox(height: 10),
          _StatTile(
            label: 'Average Score',
            value: '${avgScore.toStringAsFixed(1)}%',
            icon: Icons.bar_chart,
            bg: const Color(0xFFD1FAE5),
            iconBg: const Color(0xFFA7F3D0),
            valueColor: const Color(0xFF065F46),
            iconColor: const Color(0xFF065F46),
          ),
          const SizedBox(height: 10),
          _StatTile(
            label: 'Questions Answered',
            value: '$questionsAnswered',
            icon: Icons.calculate_outlined,
            bg: const Color(0xFFEDE9FE),
            iconBg: const Color(0xFFDDD6FE),
            valueColor: const Color(0xFF5B21B6),
            iconColor: const Color(0xFF5B21B6),
          ),
        ],
      ),
    );
  }
}

class _StatTile extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color bg;
  final Color iconBg;
  final Color valueColor;
  final Color iconColor;

  const _StatTile({
    required this.label,
    required this.value,
    required this.icon,
    required this.bg,
    required this.iconBg,
    required this.valueColor,
    required this.iconColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF374151),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: valueColor,
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: iconBg,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: iconColor, size: 22),
          ),
        ],
      ),
    );
  }
}

// ── Shared helpers ────────────────────────────────────────────────────────────
BoxDecoration _cardDecoration() => BoxDecoration(
  color: Colors.white,
  borderRadius: BorderRadius.circular(16),
  boxShadow: [
    BoxShadow(
      color: Colors.black.withAlpha(10),
      blurRadius: 12,
      offset: const Offset(0, 4),
    ),
  ],
);

Widget _iconBox(IconData icon) => Container(
  padding: const EdgeInsets.all(8),
  decoration: BoxDecoration(
    color: const Color(0xFFEEF2FF),
    borderRadius: BorderRadius.circular(10),
  ),
  child: Icon(icon, color: const Color(0xFF3B5BDB), size: 18),
);

Widget _emptyState(IconData icon, String message, String hint) => SizedBox(
  width: double.infinity,
  child: Padding(
    padding: const EdgeInsets.symmetric(vertical: 28),
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Icon(icon, size: 44, color: const Color(0xFFCBD5E1)),
        const SizedBox(height: 12),
        Text(
          message,
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 15,
            color: Color(0xFF64748B),
          ),
        ),
        const SizedBox(height: 4),
        Text(
          hint,
          style: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
          textAlign: TextAlign.center,
        ),
      ],
    ),
  ),
);
