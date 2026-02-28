# MathGenX - Adaptive Mathematics Learning Platform

![PHP](https://img.shields.io/badge/PHP-8.4-blue?logo=php&logoColor=white)
![Node.js](https://img.shields.io/badge/Node.js-22+-green?logo=node.js&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-red?logo=laravel&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3-yellow?logo=vite&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-4.1-06B6D4?logo=tailwindcss&logoColor=white)
![Python](https://img.shields.io/badge/Python-3.12-blue.svg)
![FastAPI](https://img.shields.io/badge/FastAPI-0.115-green.svg)

**MathGenX** is a comprehensive adaptive mathematics learning platform designed for Malaysian Form 1 (Secondary 1) mathematics education. It combines a modern **Laravel 12** backend, a **Vue 3** single-page application frontend, and an advanced **RAG (Retrieval-Augmented Generation)** AI service to deliver personalized, curriculum-aligned mathematics education.

---

## 📑 Table of Contents

1. [System Overview](#system-overview)
2. [Architecture](#architecture)
3. [Technology Stack](#technology-stack)
4. [System Components](#system-components)
5. [Data Flow](#data-flow)
6. [Features](#features)
7. [Prerequisites](#prerequisites)
8. [Installation & Setup](#installation--setup)
9. [Configuration](#configuration)
10. [Development](#development)
11. [Deployment](#deployment)
12. [Testing](#testing)
13. [API Documentation](#api-documentation)
14. [Database Schema](#database-schema)
15. [Contributing](#contributing)
16. [License](#license)

---

## 🎯 System Overview

MathGenX is built on a **microservices-oriented architecture** consisting of three main components:

1. **Laravel Backend API** (`/mathgenx`) - RESTful API server handling business logic, authentication, and data persistence
2. **Vue 3 Frontend** (`/mathgenx/mathgenx-web`) - Modern single-page application providing the user interface
3. **RAG AI Service** (`/mathgenx-ai-prototype`) - FastAPI-based service for AI-powered question generation and adaptive chatbot assistance

The platform implements adaptive learning through:
- **VARK Learning Style Profiling** - Identifies student's preferred learning modality (Visual, Auditory, Reading, Kinesthetic)
- **DSKP Curriculum Alignment** - Follows Malaysian Ministry of Education's Dokumen Standard Kurikulum dan Pentaksiran
- **AI-Powered Question Generation** - Generates personalized questions using RAG technology
- **Adaptive Chatbot** - Provides contextual help based on student's learning style and question context
- **Learning Analytics** - Tracks student progress and performance metrics

---

## 🏗️ Architecture

### High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        Vue 3 Frontend                           │
│                    (mathgenx-web/)                             │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐              │
│  │   Auth     │  │ MathQuest  │  │  Analytics │              │
│  │   Views    │  │   Views    │  │   Views    │              │
│  └────────────┘  └────────────┘  └────────────┘              │
│         │              │              │                        │
│         └──────────────┼──────────────┘                        │
│                        │                                        │
│                   HTTP/REST API                                 │
└────────────────────────┼────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Laravel 12 Backend API                        │
│                        (app/)                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              API Controllers                            │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │   │
│  │  │   Auth       │  │  MathQuest   │  │   Chatbot    │ │   │
│  │  │  Controller  │  │  Controller  │  │  Controller  │ │   │
│  │  └──────────────┘  └──────────────┘  └──────────────┘ │   │
│  └─────────────────────────────────────────────────────────┘   │
│                         │                                       │
│         ┌───────────────┼───────────────┐                       │
│         │               │               │                       │
│         ▼               ▼               ▼                       │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐                 │
│  │  Models  │    │ Database │    │   RAG    │                 │
│  │          │    │  MySQL   │    │   API    │                 │
│  └──────────┘    └──────────┘    └──────────┘                 │
└───────────────────────────────┬─────────────────────────────────┘
                                │
                                │ HTTP Request/Response
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                   RAG AI Service                                │
│              (mathgenx-ai-prototype/)                          │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              FastAPI Application                        │   │
│  │  ┌──────────────────┐        ┌──────────────────┐      │   │
│  │  │ Question Gen RAG │        │  Chatbot RAG     │      │   │
│  │  │  (/rag-generate) │        │ (/chatbot-help)  │      │   │
│  │  └──────────────────┘        └──────────────────┘      │   │
│  │         │                            │                  │   │
│  │         ▼                            ▼                  │   │
│  │  ┌─────────────────────────────────────────┐            │   │
│  │  │   LLM Provider (Gemini/Claude)         │            │   │
│  │  └─────────────────────────────────────────┘            │   │
│  └─────────────────────────────────────────────────────────┘   │
│         │                            │                          │
│         ▼                            ▼                          │
│  ┌──────────────┐          ┌──────────────┐                    │
│  │ CSV Dataset  │          │   Templates  │                    │
│  │  (96 Qs)     │          │   (JSON)     │                    │
│  └──────────────┘          └──────────────┘                    │
└─────────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

#### 1. Authentication Flow
```
User → Vue Frontend → Laravel API → Sanctum Token → LocalStorage → Authenticated Requests
```

#### 2. Question Generation Flow
```
User Input (Topic/Level/VARK) 
  → Vue Frontend 
  → Laravel MathQuestController 
  → RAG API Service 
  → LLM Provider (Gemini/Claude) 
  → Generated Questions 
  → Laravel Database (QuestionBank) 
  → Response to Frontend
```

#### 3. Practice Session with Chatbot Flow
```
User Selects Question 
  → Practice Session Started 
  → User Asks for Help 
  → Vue Frontend 
  → Laravel ChatbotController 
  → RAG Chatbot Service 
  → LLM Provider 
  → Contextual Response 
  → Conversation History Saved 
  → Response to Frontend
```

#### 4. Analytics Flow
```
Quiz Attempts & Responses 
  → Database (QuizAttempt, QuizResponse, StudentProgress) 
  → Laravel AnalyticsController 
  → Aggregated Data 
  → Vue Frontend Dashboard 
  → Visual Charts & Metrics
```

---

## 💻 Technology Stack

### Backend
- **Framework**: Laravel 12.0
- **Language**: PHP 8.2+
- **Database**: MySQL 8.0
- **Cache**: Redis 7
- **Authentication**: Laravel Sanctum
- **Permissions**: Spatie Laravel Permission
- **API Documentation**: Scribe (Knuckles Wtf)

### Frontend
- **Framework**: Vue 3.5 (Composition API, `<script setup>`)
- **Language**: TypeScript 5.9
- **Build Tool**: Vite 7
- **State Management**: Pinia 3
- **Routing**: Vue Router 4
- **HTTP Client**: Ky
- **UI Library**: Preline 3
- **Styling**: TailwindCSS 4.1
- **Icons**: Lucide Vue Next
- **Charts**: Chart.js + Vue Chart.js
- **Animations**: GSAP, Motion-V

### AI Service
- **Framework**: FastAPI 0.115
- **Language**: Python 3.12+
- **LLM Providers**: 
  - Google Gemini 2.5/3 Flash
  - Anthropic Claude Sonnet 4.5
- **RAG Architecture**: Dual RAG System (Question Generation + Chatbot)

### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Web Server**: Nginx
- **Process Manager**: PHP-FPM

---

## 🧩 System Components

### 1. Laravel Backend (`/app`)

#### Controllers
- **`Api/AuthCustomController`**: User registration, login, OAuth (Google), profile management
- **`Api/ProfilingController`**: VARK learning style assessment, Math proficiency profiling
- **`Api/MathQuestController`**: Quiz generation, question retrieval, attempt submission
- **`Api/ChatbotController`**: Practice session chatbot interaction, message history
- **`Api/MathPracticeController`**: Practice question management from question bank
- **`Api/AnalyticsController`**: Learning analytics, progress tracking, dashboard metrics
- **`Api/ProfileController`**: User profile updates
- **`Api/BillplzController`**: Payment gateway integration

#### Models
- **User Management**: `User`, `Role`, `Permission`
- **Profiling**: `VarkQuestion`, `VarkAnswer`, `VarkResponse`, `VarkResult`, `MathQuestion`, `MathAnswer`, `MathResponse`, `MathResult`
- **Content**: `DskpMain`, `DskpTopic`, `DskpSubtopic`, `DskpCriteria`, `DskpMastery`, `TeachingMaterial`, `TeachingContent`, `AssessmentMaterial`, `AssessmentContent`
- **Learning**: `QuestionBank`, `QuizAttempt`, `QuizResponse`, `StudentProgress`, `PracticeChatMessage`, `ClassRoom`

#### API Routes (`routes/api.php`)
All API routes are prefixed with `/api/v1/` and protected by `auth:sanctum` middleware where applicable:
- `/api/v1/auth/*` - Authentication endpoints
- `/api/v1/profiling/*` - Profiling assessments
- `/api/v1/mathquest/*` - Quiz generation and management
- `/api/v1/mathpractice/*` - Practice questions and chatbot
- `/api/v1/analytics/*` - Learning analytics
- `/api/v1/profile/*` - User profile management

### 2. Vue 3 Frontend (`/mathgenx-web`)

#### Views
- **Authentication**: `LandingPage`, `LoginView`, `RegisterView`, `GoogleCallbackView`
- **Onboarding**: `ProfilingView` (VARK + Math assessments)
- **Main Application**: 
  - `DashboardView` - Learning dashboard with analytics
  - `MathQuestView` - Quiz interface
  - `QuestionBuilder` - Quiz configuration and generation
  - `QuizReviewView` - Review completed quizzes
  - `QuizAttemptsView` - Quiz attempt history
  - `PracticeBuilder` - Practice session configuration
  - `MathPracticeView` - Practice interface with chatbot
  - `UserProfileView` - User settings and profile

#### Layouts
- `LandingLayout` - Public landing page
- `AuthLayout` - Login/Register pages
- `OnBoardingLayout` - Profiling assessment
- `MainLayout` - Authenticated application (with sidebar navigation)

#### Stores (Pinia)
- `authStore` - Authentication state, user profile, learning results
- `MathQuestStore` - Quiz state management
- `ProfilingStore` - Profiling assessment state

#### Services
- API service modules for each feature (auth, mathquest, profiling, analytics)

### 3. RAG AI Service (`/mathgenx-ai-prototype`)

#### Dual RAG Architecture

**System 1: Question Generation RAG**
- **Endpoint**: `POST /api/rag-generate`
- **Purpose**: Generates 10 personalized math questions per request
- **Data Source**: CSV dataset (96 curriculum-aligned questions)
- **Features**: 
  - Level-based system (Levels 1-12 map to TP1-TP6)
  - Batch generation (7 easy + 3 hard or 3 easy + 7 hard)
  - Word limits based on TP level
  - VARK style adaptation
  - Multiple choice and subjective formats

**System 2: Chatbot RAG**
- **Endpoint**: `POST /api/chatbot-help`
- **Purpose**: Provides adaptive help for students working on existing questions
- **Data Source**: Answer templates (JSON) + DSKP context
- **Features**:
  - Intent detection (hint, explanation, step-by-step)
  - Language auto-detection (English/Malay)
  - Conversation history management
  - VARK-adapted responses
  - Contextual decision making

For detailed AI service documentation, refer to [`mathgenx-ai-prototype/README.md`](../mathgenx-ai-prototype/README.md).

---

## 🔄 Data Flow

### Question Generation Process

1. **User Input** (Frontend)
   - Selects topic, subtopic (optional), level (1-12), question format, language
   - VARK style retrieved from user profile

2. **Request to Laravel** (`MathQuestController::generateQuestion`)
   - Validates input
   - Checks for cached/generated questions in database
   - If not found, proceeds to RAG API

3. **RAG API Call**
   - Laravel sends HTTP POST to RAG service (`/api/rag-generate`)
   - Payload includes: topic, subtopic, level, vark_style, question_format, language

4. **RAG Processing** (AI Service)
   - **Retrieval**: Fetches similar examples from CSV dataset based on mastery level and VARK style
   - **Augmentation**: Loads DSKP context, VARK templates, question templates, combines with examples
   - **Generation**: Single LLM API call generates 10 questions in batch
   - **Parsing**: Converts LLM response to structured JSON

5. **Response Processing** (Laravel)
   - Receives generated questions from RAG API
   - Saves to `question_banks` table
   - Formats questions for frontend
   - Returns to Vue frontend

6. **Display** (Frontend)
   - Questions rendered in quiz interface
   - User answers questions
   - Responses submitted and saved to `quiz_responses` table

### Chatbot Assistance Process

1. **User Request for Help** (Frontend)
   - User working on a practice question
   - Types help request: "I don't understand" / "Give me a hint" / "Show me steps"

2. **Request to Laravel** (`ChatbotController::sendMessage`)
   - Validates session and question
   - Retrieves conversation history from `practice_chat_messages` table
   - Formats answer based on question type (MCQ vs subjective)

3. **RAG API Call**
   - Laravel sends HTTP POST to RAG service (`/api/chatbot-help`)
   - Payload includes: practice_session_id, vark_style, question, answer, user_prompt, topic, subtopic, chat_history

4. **Chatbot Processing** (AI Service)
   - **Language Detection**: Analyzes user input for language
   - **Intent Detection**: Matches keywords (hint, explain, steps)
   - **Retrieval**: Loads answer templates, VARK-specific styles, DSKP context
   - **Augmentation**: Combines question, answer, history, VARK style, intent
   - **Generation**: LLM generates adaptive response (30-50 words)

5. **Response Processing** (Laravel)
   - Saves user message and assistant response to database
   - Returns response to frontend

6. **Display** (Frontend)
   - Chat interface shows conversation
   - User can continue asking questions

### Analytics Tracking Process

1. **Data Collection**
   - Every quiz attempt saved to `quiz_attempts` table
   - Every response saved to `quiz_responses` table
   - Progress automatically calculated and stored in `student_progress` table

2. **Analytics Aggregation** (`AnalyticsController`)
   - Aggregates data by topic, subtopic, mastery level
   - Calculates metrics: average scores, improvement rates, time spent
   - Retrieves best scores, total attempts, correct/incorrect counts

3. **Dashboard Display** (Frontend)
   - Charts show progress over time
   - Metrics displayed in cards
   - Topic-wise breakdown available

---

## ✨ Features

### 1. User Authentication & Management
- Email/password registration and login
- Google OAuth integration
- Laravel Sanctum token-based authentication
- Profile management
- Role-based access control (Spatie Permissions)

### 2. Learning Style Profiling
- **VARK Assessment**: 16-question assessment to determine learning style (Visual, Auditory, Reading, Kinesthetic)
- **Math Profiling**: Initial assessment to determine starting mastery level (TP1-TP6)
- Results stored and used for personalization throughout the platform

### 3. Adaptive Question Generation (MathQuest)
- AI-powered question generation using RAG
- Personalized based on:
  - VARK learning style
  - Mastery level (TP1-TP6)
  - Topic and subtopic selection
  - Difficulty preference (easy/hard mix)
- Multiple formats: Multiple choice, Subjective
- Bilingual support: English, Bahasa Malaysia
- Questions saved to question bank for reuse

### 4. Practice Mode with AI Chatbot
- Practice with questions from question bank
- AI-powered chatbot assistance:
  - Contextual hints
  - Step-by-step explanations
  - Adaptive responses based on VARK style
  - Language-aware (responds in same language as user)
  - Conversation history maintained
- Real-time help while working on questions

### 5. Learning Analytics
- Comprehensive progress tracking:
  - Score trends over time
  - Topic-wise performance
  - Mastery level progression
  - Time spent analysis
  - Improvement rate calculation
- Visual dashboards with charts
- Performance metrics and insights

### 6. Quiz Management
- Quiz attempt history
- Detailed review of completed quizzes
- Answer explanations
- Performance analysis per quiz

### 7. Curriculum Alignment (DSKP)
- Follows Malaysian Form 1 mathematics curriculum
- Topic, subtopic, and criteria alignment
- Mastery levels (TP1-TP6) mapped to curriculum standards

---

## 📋 Prerequisites

### Required Software
- **PHP** ≥ 8.2
- **Composer** ≥ 2.x
- **Node.js** ≥ 18 and **npm**
- **MySQL** 8.0+ / **MariaDB** 10.3+
- **Redis** 7+ (for caching and queues)
- **Git**
- **Python** 3.12+ (for RAG AI service)
- **Docker & Docker Compose** (optional, for containerized deployment)

### Recommended Tools
- **Laragon** (Windows) - Auto virtual host configuration
- **Laravel Valet** (macOS) - Auto virtual host configuration
- **Homestead** (Ubuntu) - Auto virtual host configuration

### API Keys Required
- **Google OAuth** (optional): `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`
- **LLM Provider** (required for AI features):
  - Gemini: `GEMINI_API_KEY`
  - OR Claude: `ANTHROPIC_API_KEY`
- **Mail Service** (optional): SMTP credentials for email notifications

---

## 🚀 Installation & Setup

### Step 1: Clone the Repository

```bash
# Windows (Laragon)
cd C:\laragon\www

# Ubuntu / macOS
cd ~/Projects  # or your preferred directory

# Clone repository
git clone https://github.com/camikemal/MathGenX.git
cd MathGenX
```

### Step 2: Set Up Laravel Backend

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (for Laravel Blade frontend)
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 3: Configure Environment

Edit `.env` file:

```env
APP_NAME=MathGenX
APP_ENV=local
APP_DEBUG=true
APP_URL=http://mathgenx.test

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mathgenx_local
DB_USERNAME=root
DB_PASSWORD=

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=database

# RAG AI Service URLs
RAG_API_URL=http://localhost:8000/api/rag-generate
RAG_API_TIMEOUT=300
CHATBOT_API_URL=http://localhost:8000/api/chatbot-help
CHATBOT_API_TIMEOUT=300

# OAuth (Optional)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# Mail Configuration (for testing, use Mailtrap)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 4: Set Up Database

```bash
# Create database (or let Laravel create it)
# In MySQL: CREATE DATABASE mathgenx_local;

# Run migrations and seeders
php artisan migrate --seed

# Create storage symlink
php artisan storage:link
```

### Step 5: Set Up Vue 3 Frontend

```bash
# Navigate to frontend directory
cd mathgenx-web

# Install dependencies
npm install

# Copy environment file
cp .env.example .env
```

Edit `mathgenx-web/.env`:

```env
VITE_APP_NAME=MathGenX
VITE_API_BASE=http://mathgenx.test/api/v1
```

### Step 6: Set Up RAG AI Service

Refer to [`mathgenx-ai-prototype/README.md`](../mathgenx-ai-prototype/README.md) for detailed setup instructions.

**Quick Setup:**

```bash
# Navigate to AI service directory
cd ../mathgenx-ai-prototype

# Create virtual environment
python3 -m venv venv_mathgenx
source venv_mathgenx/bin/activate  # On macOS/Linux
# venv_mathgenx\Scripts\activate   # On Windows

# Install dependencies
pip install -r requirements.txt

# Create .env file
cat > .env << EOF
LLM_PROVIDER=gemini
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-2.5-flash
EOF

# Start server
chmod +x start_server.sh
./start_server.sh
# OR manually: uvicorn app:app --host 0.0.0.0 --port 8000 --reload
```

### Step 7: Start Development Servers

**Terminal 1 - Laravel Backend:**
```bash
# From project root
composer dev
# This runs: php artisan serve, npm run dev (for Blade), queue:listen, and logs
```

**Terminal 2 - Vue Frontend:**
```bash
cd mathgenx-web
npm run dev
```

**Terminal 3 - RAG AI Service:**
```bash
cd mathgenx-ai-prototype
source venv_mathgenx/bin/activate
./start_server.sh
```

### Step 8: Access the Application

- **Vue Frontend**: `http://localhost:5173` (Vite dev server)
- **Laravel Backend**: `http://mathgenx.test` (if using Laragon/Valet)
  - OR: `http://127.0.0.1:8000` (if using `php artisan serve`)
- **RAG AI Service**: `http://localhost:8000/docs` (FastAPI interactive docs)
- **API Documentation**: `http://mathgenx.test/docs` (Scribe-generated)

---

## ⚙️ Configuration

### Laravel Backend Configuration

#### RAG Service Integration
Configuration in `config/services.php`:
```php
'rag' => [
    'url' => env('RAG_API_URL', 'http://localhost:8000/api/rag-generate'),
    'timeout' => env('RAG_API_TIMEOUT', 300),
],
'chatbot' => [
    'url' => env('CHATBOT_API_URL', 'http://localhost:8000/api/chatbot-help'),
    'timeout' => env('CHATBOT_API_TIMEOUT', 300),
],
```

#### CORS Configuration
Ensure `config/cors.php` allows requests from your Vue frontend URL:
```php
'allowed_origins' => [env('APP_VUE_APP_URL', 'http://localhost:5173')],
```

### Vue Frontend Configuration

#### API Base URL
In `mathgenx-web/.env`:
```env
VITE_API_BASE=http://mathgenx.test/api/v1
```

#### API Service Configuration
API services are configured in `mathgenx-web/src/services/` using the `VITE_API_BASE` environment variable.

### RAG AI Service Configuration

See [`mathgenx-ai-prototype/README.md`](../mathgenx-ai-prototype/README.md) for detailed AI service configuration.

---

## 💻 Development

### Laravel Development

```bash
# Run all services concurrently
composer dev

# Individual services
php artisan serve              # Development server
npm run dev                    # Vite (Blade frontend)
php artisan queue:listen       # Queue worker
php artisan pail --timeout=0   # Log viewer

# Code formatting
vendor/bin/pint

# Clear caches
php artisan optimize:clear

# Database
php artisan migrate            # Run migrations
php artisan migrate:fresh --seed  # Fresh migration with seeders
php artisan db:seed            # Run seeders only
```

### Vue Frontend Development

```bash
cd mathgenx-web

# Development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Type checking
vue-tsc -b
```

### RAG AI Service Development

```bash
cd mathgenx-ai-prototype
source venv_mathgenx/bin/activate

# Start development server with auto-reload
uvicorn app:app --host 0.0.0.0 --port 8000 --reload

```

---

## 🐳 Deployment

### Docker Compose Deployment

The project includes a `docker-compose.yml` for containerized deployment:

```bash
# Build and start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down

# Rebuild services
docker-compose up -d --build
```

**Services included:**
- `db` - MySQL 8.0 database
- `app` - Laravel PHP-FPM application
- `nginx` - Nginx web server
- `frontend` - Vue frontend (static files)
- `rag-api` - FastAPI RAG service
- `redis` - Redis cache and queue

**Environment Variables for Docker:**
Create `.env` file in project root with:
```env
DB_DATABASE=mathgenx
DB_USERNAME=mathgenx
DB_PASSWORD=your_secure_password
DB_ROOT_PASSWORD=your_root_password
REDIS_PASSWORD=your_redis_password
RAG_API_PATH=../mathgenx-ai-prototype
```

### Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Configure production database credentials
- [ ] Set up SSL certificates (HTTPS)
- [ ] Configure production RAG API URLs
- [ ] Set up queue workers (supervisor or systemd)
- [ ] Configure Redis for caching and queues
- [ ] Set up backup strategy for database
- [ ] Configure email service (production SMTP)
- [ ] Set up monitoring and error tracking
- [ ] Enable API rate limiting
- [ ] Configure CORS for production domain
- [ ] Build Vue frontend: `cd mathgenx-web && npm run build`
- [ ] Optimize Laravel: `php artisan optimize`

---

## 📚 API Documentation

### Scribe Documentation

API documentation is automatically generated using Scribe:

```bash
# Generate API docs
php artisan scribe:generate

# View docs
# Visit: http://mathgenx.test/docs
```

### FastAPI Interactive Docs

RAG AI service includes interactive API documentation:

- **Swagger UI**: `http://localhost:8000/docs`
- **ReDoc**: `http://localhost:8000/redoc`

### API Endpoints Overview

#### Authentication
- `POST /api/v1/auth/register` - User registration
- `POST /api/v1/auth/login` - User login
- `GET /api/v1/auth/google/redirect` - Google OAuth redirect
- `GET /api/v1/auth/google/callback` - Google OAuth callback
- `POST /api/v1/auth/logout` - User logout (auth required)
- `GET /api/v1/auth/profile` - Get user profile (auth required)

#### Profiling
- `GET /api/v1/profiling/vark/questions` - Get VARK questions (auth required)
- `POST /api/v1/profiling/vark/submit` - Submit VARK responses (auth required)
- `GET /api/v1/profiling/math/questions` - Get Math profiling questions (auth required)
- `POST /api/v1/profiling/math/submit` - Submit Math responses (auth required)
- `POST /api/v1/profiling/complete` - Complete profiling (auth required)

#### MathQuest
- `GET /api/v1/mathquest/quiz` - Generate/retrieve quiz questions (auth required)
- `POST /api/v1/mathquest/attempts` - Submit quiz attempt (auth required)
- `GET /api/v1/mathquest/attempts` - Get user's quiz attempts (auth required)
- `GET /api/v1/mathquest/attempts/{id}` - Get specific attempt details (auth required)

#### MathPractice
- `GET /api/v1/mathpractice/questions` - Get practice questions from bank (auth required)
- `POST /api/v1/mathpractice/chatbot/message` - Send chatbot message (auth required)
- `GET /api/v1/mathpractice/chatbot/history` - Get chatbot conversation history (auth required)

#### Analytics
- `GET /api/v1/analytics/progress` - Get student progress data (auth required)
- `GET /api/v1/analytics/dashboard` - Get dashboard analytics (auth required)

#### Profile
- `PUT /api/v1/profile/update` - Update user profile (auth required)

For detailed request/response schemas, refer to Scribe documentation or FastAPI interactive docs.

---

## 🗄️ Database Schema

### Core Tables

#### User Management
- `users` - User accounts
- `model_has_roles` - User roles (Spatie Permissions)
- `roles` - Role definitions
- `permissions` - Permission definitions

#### Profiling
- `vark_questions` - VARK assessment questions
- `vark_answers` - VARK answer options
- `vark_responses` - User VARK responses
- `vark_results` - User VARK learning style results
- `math_questions` - Math profiling questions
- `math_answers` - Math answer options
- `math_responses` - User math responses
- `math_results` - User math proficiency results

#### Content (DSKP)
- `dskp_mains` - Main curriculum categories
- `dskp_topics` - Topics
- `dskp_subtopics` - Subtopics
- `dskp_criterias` - Learning criteria
- `dskp_masteries` - Mastery levels (TP1-TP6)
- `teaching_materials` - Teaching material sets
- `teaching_content` - Teaching content items
- `assessment_materials` - Assessment material sets
- `assessment_contents` - Assessment content items

#### Learning
- `question_banks` - Generated and stored questions
- `quiz_attempts` - Quiz attempt records
- `quiz_responses` - Individual question responses
- `student_progress` - Aggregated progress tracking
- `practice_chat_messages` - Chatbot conversation history
- `class_rooms` - Classroom management

### Key Relationships

- `User` → `VarkResult` (1:1)
- `User` → `MathResult` (1:1)
- `User` → `QuizAttempt` (1:N)
- `QuizAttempt` → `QuizResponse` (1:N)
- `QuizResponse` → `QuestionBank` (N:1)
- `User` → `StudentProgress` (1:N, per topic/subtopic)
- `User` → `PracticeChatMessage` (1:N)

### ER Diagram Overview

```
Users
  ├── VarkResults (1:1)
  ├── MathResults (1:1)
  ├── QuizAttempts (1:N)
  │     └── QuizResponses (1:N)
  │           └── QuestionBanks (N:1)
  ├── StudentProgress (1:N)
  └── PracticeChatMessages (1:N)

QuestionBanks
  ├── DskpTopics (N:1)
  ├── DskpSubtopics (N:1)
  └── DskpMasteries (N:1)
```

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards (PHP)
- Use ESLint/Prettier for JavaScript/TypeScript
- Write tests for new features
- Update documentation as needed
- Follow Git commit message conventions

---

## 📄 License

This project is licensed under the MIT License.

---

## 👨‍💻 Authors

**Camikemal**
- GitHub: [@camikemal](https://github.com/camikemal)
- Repository: [MathGenX](https://github.com/camikemal/MathGenX)

---

## 🙏 Acknowledgments

- Malaysian Ministry of Education (DSKP Curriculum Framework)
- Google Gemini AI Platform
- Anthropic Claude AI Platform
- Laravel Framework
- Vue.js Team
- FastAPI Framework
- VARK Learning Styles Model (Neil Fleming)
- Preline UI Component Library

---

## 📖 Additional Resources

- **Laravel Documentation**: https://laravel.com/docs/12.x
- **Vue 3 Documentation**: https://vuejs.org/
- **FastAPI Documentation**: https://fastapi.tiangolo.com/
- **DSKP Framework**: Malaysian Ministry of Education
- **VARK Learning Styles**: https://vark-learn.com/

---

**Made with ❤️ for Malaysian Education**

---

## 📝 Changelog

### Version 1.0.0
- Initial release
- Dual RAG architecture for question generation and chatbot
- VARK learning style profiling
- DSKP curriculum alignment
- Comprehensive learning analytics
- Vue 3 SPA frontend
- Laravel 12 backend API
- Docker deployment support
