class VarkScores {
  final int scoreV;
  final int scoreA;
  final int scoreR;
  final int scoreK;

  const VarkScores({
    required this.scoreV,
    required this.scoreA,
    required this.scoreR,
    required this.scoreK,
  });

  factory VarkScores.fromJson(Map<String, dynamic> json) => VarkScores(
    scoreV: (json['score_v'] as num).toInt(),
    scoreA: (json['score_a'] as num).toInt(),
    scoreR: (json['score_r'] as num).toInt(),
    scoreK: (json['score_k'] as num).toInt(),
  );
}

class LearningResults {
  final String vark;
  final VarkScores varkScores;
  final String math;
  final int totalScore;

  const LearningResults({
    required this.vark,
    required this.varkScores,
    required this.math,
    required this.totalScore,
  });

  factory LearningResults.fromJson(Map<String, dynamic> json) =>
      LearningResults(
        vark: json['vark'] as String,
        varkScores: VarkScores.fromJson(
          json['vark_scores'] as Map<String, dynamic>,
        ),
        math: json['math'] as String,
        totalScore: (json['total_score'] as num).toInt(),
      );
}

class ExpInfo {
  final int exp;
  final int expInCurrentLevel;
  final int expNeededForNextLevel;
  final int totalExpForNextLevel;
  final double expProgress;

  const ExpInfo({
    required this.exp,
    required this.expInCurrentLevel,
    required this.expNeededForNextLevel,
    required this.totalExpForNextLevel,
    required this.expProgress,
  });

  factory ExpInfo.fromJson(Map<String, dynamic> json) => ExpInfo(
    exp: (json['exp'] as num).toInt(),
    expInCurrentLevel: (json['exp_in_current_level'] as num).toInt(),
    expNeededForNextLevel: (json['exp_needed_for_next_level'] as num).toInt(),
    totalExpForNextLevel: (json['total_exp_for_next_level'] as num).toInt(),
    expProgress: (json['exp_progress'] as num).toDouble(),
  );
}

class UserProfile {
  // User basics
  final String name;
  final String email;
  final bool hasCompletedProfiling;
  final int level;
  final int exp;

  // Roles
  final List<String> roles;

  // Learning results (nullable — user may not have completed profiling)
  final LearningResults? learningResults;

  // EXP info
  final ExpInfo expInfo;

  const UserProfile({
    required this.name,
    required this.email,
    required this.hasCompletedProfiling,
    required this.level,
    required this.exp,
    required this.roles,
    this.learningResults,
    required this.expInfo,
  });

  factory UserProfile.fromJson(Map<String, dynamic> data) {
    final user = data['user'] as Map<String, dynamic>;
    final roles = (data['roles'] as List<dynamic>).cast<String>();
    final learningResultsJson = data['learning_results'];
    final expInfoJson = data['exp_info'] as Map<String, dynamic>;

    return UserProfile(
      name: user['name'] as String,
      email: user['email'] as String,
      hasCompletedProfiling: (user['has_completed_profiling'] as num) != 0,
      level: (user['level'] as num).toInt(),
      exp: (user['exp'] as num).toInt(),
      roles: roles,
      learningResults: learningResultsJson != null
          ? LearningResults.fromJson(
              learningResultsJson as Map<String, dynamic>,
            )
          : null,
      expInfo: ExpInfo.fromJson(expInfoJson),
    );
  }
}
