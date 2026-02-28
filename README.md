# MathGenX Mobile

MathGenX Mobile is an intelligent, cross-platform educational application designed to provide personalised math learning experiences through the power of generative AI.

## 1. Technical Architecture
The application leverages a robust, multi-tier architecture designed for scalability, performance, and a seamless user experience across platforms:
- **Frontend:** Built with **Flutter**, ensuring a unified, reactive, and natively compiled mobile experience for both iOS and Android users from a single codebase.
- **Backend Services:**
  - **Laravel API:** Handles the core business logic, user authentication, profile management, and standard data operations.
  - **Python FastAPI:** A dedicated ultra-fast microservice utilized specifically to manage the asynchronous AI workloads and model interactions efficiently.
- **Database:** **MySQL** serves as the reliable relational database for structurally secure storage of user data and application state.
- **AI Engine:** **Google Gemini** powers the core intelligent features, providing state-of-the-art natural language understanding and generation capabilities.

## 2. Implementation Details
The core innovation of MathGenX lies in its adaptive question generation engine:
- **Personalised Learning via RAG:** We employ a RAG (Retrieval-Augmented Generation) architecture to dynamically generate highly personalised math questions. Rather than relying on a static database of questions, the system retrieves relevant educational context tailored to a user's specific learning style and past performance.
- **Gemini-Powered Generation:** Google Gemini acts as the core reasoning engine within this RAG pipeline. It synthesizes the retrieved contextual data to formulate unique, appropriately challenging, and pedagogically sound questions on the fly.

## 3. Challenges Faced
Developing an AI-driven educational platform presented several unique engineering hurdles:
- **Mitigating AI Hallucination:** A primary challenge was ensuring the AI consistently outputs mathematically correct and strictly structured questions. Raw generation often leads to nuanced errors (hallucinations) in complex math problems. This required extensive prompt engineering, output validation loops, and stringent structuring to guarantee high-quality quizzes.
- **Optimizing RAG Robustness:** Building a robust RAG system requires a rich vector foundation. We faced challenges with limited initial domain-specific data, making it difficult to fully optimize the retrieval mechanisms and ensure the contextual grounding was strong enough for every possible learning style.

## 4. Future Roadmap
We are continuously expanding the learning capabilities of MathGenX. Upcoming features include:
- **Interactive Drill Sessions:** Implementation of an interactive chatbot assistant to guide users through practice "Drills," offering step-by-step hints, explanations, and real-time tutoring.
- **Comprehensive Quiz History:** A detailed analytics dashboard allowing users to review extensive past quiz histories, analyze their answers, track progress margins, and identify areas for improvement over time.
