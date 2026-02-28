import 'package:flutter/material.dart';

class DrillPage extends StatelessWidget {
  const DrillPage({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: Color(0xFFF8FAFF),
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.fitness_center, size: 64, color: Color(0xFFCBD5E1)),
            SizedBox(height: 16),
            Text(
              'Drill',
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w700,
                color: Color(0xFF0F172A),
              ),
            ),
            SizedBox(height: 8),
            Text(
              'Coming Soon',
              style: TextStyle(fontSize: 15, color: Color(0xFF94A3B8)),
            ),
          ],
        ),
      ),
    );
  }
}
