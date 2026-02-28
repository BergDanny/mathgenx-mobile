import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

// Lazy load routes for code splitting
const LandingPage = () => import("@/views/LandingPage.vue");
const LoginView = () => import("@/views/Auth/LoginView.vue");
const RegisterView = () => import("@/views/Auth/RegisterView.vue");
const GoogleCallbackView = () => import("@/views/Auth/GoogleCallbackView.vue");
const DashboardView = () => import("@/views/DashboardView.vue");
const ProfilingView = () => import("@/views/Profiling/ProfilingView.vue");
const MathQuestView = () => import("@/views/MathQuest/MathQuestView.vue");
const QuestionBuilder = () => import("@/views/MathQuest/QuestionBuilder.vue");
const QuizReviewView = () => import("@/views/MathQuest/QuizReviewView.vue");
const QuizAttemptsView = () => import("@/views/MathQuest/QuizAttemptsView.vue");
const Test = () => import("@/views/MathQuest/Test.vue");
const PracticeBuilder = () => import("@/views/MathPractice/PracticeBuilder.vue");
const MathPracticeView = () => import("@/views/MathPractice/MathPracticeView.vue");
const UserProfileView = () => import("@/views/UserProfile/UserProfileView.vue");

// Helper function to check if profiling is incomplete
const hasIncompleteProfiling = (learningResults: any) => {
  return !learningResults || 
        learningResults.vark === null || 
        learningResults.math === null;
};

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior(_to, _from, savedPosition) {
    // If there's a saved position (e.g., browser back/forward), use it
    if (savedPosition) {
      return savedPosition;
    }
    // Otherwise, scroll to top
    return { top: 0, behavior: 'smooth' };
  },
  routes: [
    {
      path: "/",
      name: "landing",
      component: LandingPage,
      meta: { layout: "landing", guestOnly: true },
      beforeEnter: async (_to, _from, next) => {
        const token = localStorage.getItem("token");
        if (token) {
          const authStore = useAuthStore();
          // Always validate token by fetching profile
          const profileRes = await authStore.fetchProfile();
          // If profile fetch failed (e.g., expired token), clear token and go to landing
          if (!profileRes.success || profileRes._status === 401 || profileRes._status === 403) {
            authStore.logout();
            next();
            return;
          }
          // Only redirect to profiling if user is authenticated AND profiling is incomplete
          if (profileRes.success && hasIncompleteProfiling(authStore.learning_results)) {
            next({ name: "profiling" });
          } else {
            next({ name: "dashboard" });
          }
        } else {
          next();
        }
      },
    },
    // Catch-all route to redirect unknown paths to home
    {
      path: "/:pathMatch(.*)*",
      redirect: "/",
    },
    // Auth routes
    {
      path: "/login",
      name: "login",
      component: LoginView,
      meta: { layout: "auth", guestOnly: true },
    },
    {
      path: "/register",
      name: "register",
      component: RegisterView,
      meta: { layout: "auth", guestOnly: true },
    },
    {
      path: "/auth/google/callback",
      name: "google-callback",
      component: GoogleCallbackView,
      meta: { layout: "auth" },
    },

    // Onboarding routes
    {
      path: "/profiling",
      name: "profiling",
      component: ProfilingView,
      meta: { layout: "onboarding", requiresAuth: true },
      beforeEnter: async (_to, _from, next) => {
        const token = localStorage.getItem("token");
        if (!token) {
          next({ name: "landing" });
          return;
        }
        const authStore = useAuthStore();
        // Validate token by fetching profile
        const profileRes = await authStore.fetchProfile();
        // If profile fetch failed (e.g., expired token), logout and go to landing
        if (!profileRes.success || profileRes._status === 401 || profileRes._status === 403) {
          authStore.logout();
          next({ name: "landing" });
          return;
        }
        // Allow access if user is authenticated (users can retake assessment even if profiling is complete)
        if (profileRes.success && authStore.user) {
          next();
        } else {
          next({ name: "landing" });
        }
      },
    },

    // Main app routes
    {
      path: "/dashboard",
      name: "dashboard",
      component: DashboardView,
      meta: { layout: "main", requiresAuth: true },
      beforeEnter: async (_to, _from, next) => {
        const authStore = useAuthStore();
        // Ensure profile is loaded
        if (!authStore.learning_results && authStore.token) {
          const profileRes = await authStore.fetchProfile();
          // If profile fetch failed (e.g., expired token), logout and go to landing
          if (!profileRes.success || profileRes._status === 401 || profileRes._status === 403) {
            authStore.logout();
            next({ name: "landing" });
            return;
          }
        }
        // Only redirect to profiling if user is authenticated AND profiling is incomplete
        if (authStore.user && hasIncompleteProfiling(authStore.learning_results)) {
          next({ name: "profiling" });
        } else {
          next();
        }
      },
    },

    // MathQuest routes
    {
      path: "/mathquest/quiz",
      name: "mathquest_quiz",
      component: MathQuestView,
      meta: { layout: "main", requiresAuth: true },
    },
    {
      path: "/mathquest",
      name: "mathquest_builder",
      component: QuestionBuilder,
      meta: { layout: "main", requiresAuth: true },
    },
    {
      path: "/mathquest/attempts",
      name: "quiz_attempts",
      component: QuizAttemptsView,
      meta: { layout: "main", requiresAuth: true },
    },
    {
      path: "/mathquest/review/:id",
      name: "quiz_review",
      component: QuizReviewView,
      meta: { layout: "main", requiresAuth: true },
    },
    {
      path: "/mathquest/test",
      name: "mathquest_test",
      component: Test,
      meta: { layout: "main", requiresAuth: true },
    },

    // MathPractice routes
    {
      path: "/mathpractice",
      name: "mathpractice_builder",
      component: PracticeBuilder,
      meta: { layout: "main", requiresAuth: true },
    },
    {
      path: "/mathpractice/practice",
      name: "mathpractice_view",
      component: MathPracticeView,
      meta: { layout: "main", requiresAuth: true },
    },

    // User Profile routes
    {
      path: "/profile",
      name: "profile_settings",
      component: UserProfileView,
      meta: { layout: "main", requiresAuth: true },
    },
  ],
});

router.beforeEach(async (to, _from) => {
  const token = localStorage.getItem("token");
  const authStore = useAuthStore();
  
  // Protect routes that need authentication
  if (to.meta.requiresAuth) {
    if (!token) {
      return { name: "landing" };
    }
    
    // Validate token by checking if we have user data, or fetch it
    if (!authStore.user || !authStore.learning_results) {
      const profileRes = await authStore.fetchProfile();
      // If profile fetch failed (e.g., expired token), logout and go to landing
      if (!profileRes.success || profileRes._status === 401 || profileRes._status === 403) {
        authStore.logout();
        return { name: "landing" };
      }
    }
    
    // Only redirect to profiling if user is authenticated AND profiling is incomplete
    // Don't redirect if already on profiling page
    if (to.name !== "profiling" && authStore.user && hasIncompleteProfiling(authStore.learning_results)) {
      return { name: "profiling" };
    }
  }
  
  // Prevent authenticated users from accessing login/register again
  if (to.meta.guestOnly && token) {
    // Always validate token by fetching profile
    const profileRes = await authStore.fetchProfile();
    // If profile fetch failed (e.g., expired token), clear token and allow access
    if (!profileRes.success || profileRes._status === 401 || profileRes._status === 403) {
      authStore.logout();
      return true;
    }
    // Only redirect to profiling if user is authenticated AND profiling is incomplete
    if (profileRes.success && hasIncompleteProfiling(authStore.learning_results)) {
      return { name: "profiling" };
    } else {
      return { name: "dashboard" };
    }
  }
  
  return true;
});

//preline auto init after each route change
router.afterEach(async (failure) => {
  if (!failure) {
    setTimeout(() => {
      window.HSStaticMethods.autoInit();
      // Ensure window is focused after navigation for immediate scroll/cursor interaction
      window.focus();
    }, 100);
  }
});

export default router;
