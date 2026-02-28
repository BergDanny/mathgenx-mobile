<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { LucideMessageCircle, LucideSend } from 'lucide-vue-next'
import { useMathPracticeStore } from '@/stores/MathPracticeStore'
import { useAuthStore } from '@/stores/authStore'
import { sendChatbotMessage, getChatHistory } from '@/services/MathPracticeService'

const mathPracticeStore = useMathPracticeStore()
const authStore = useAuthStore()

// Props
const props = defineProps({
  hideHeader: {
    type: Boolean,
    default: false
  }
})

// State
const userInput = ref('')
const messages = ref([])
const loading = ref(false)
const error = ref(null)
const messagesEndRef = ref(null)

// Computed
const currentQuestion = computed(() => mathPracticeStore.currentQuestion)
const practiceSessionId = computed(() => mathPracticeStore.practiceSessionId)
const varkStyle = computed(() => authStore.learning_results?.vark || 'Visual')

// Scroll to bottom of messages
const scrollToBottom = () => {
  setTimeout(() => {
    if (messagesEndRef.value) {
      messagesEndRef.value.scrollIntoView({ behavior: 'smooth' })
    }
  }, 100)
}

// Load chat history when question changes
const loadChatHistory = async () => {
  if (!currentQuestion.value || !practiceSessionId.value) {
    messages.value = []
    return
  }

  if (isLoadingHistory || isSendingMessage) {
    return // Prevent concurrent loads and don't reload while sending
  }

  isLoadingHistory = true
  try {
    const response = await getChatHistory(practiceSessionId.value, currentQuestion.value.id.toString())
    if (response.success && response.data?.messages) {
      // Only use database messages - don't merge with local messages to avoid duplicates
      // Convert database messages to the format expected by the UI
      messages.value = response.data.messages.map(msg => ({
        role: msg.role,
        content: msg.content,
        id: msg.id || `${msg.role}-${msg.created_at || Date.now()}-${Math.random()}`
      }))
      
      // Update store with the loaded messages
      mathPracticeStore.setChatHistory(currentQuestion.value.id.toString(), messages.value)
    } else {
      // Clear messages if no history found
      messages.value = []
    }
    scrollToBottom()
  } catch (err) {
    console.error('Failed to load chat history:', err)
    // Clear messages on error
    messages.value = []
  } finally {
    isLoadingHistory = false
  }
}

// Send message
const sendMessage = async () => {
  const prompt = userInput.value.trim()
  if (!prompt || !currentQuestion.value || !practiceSessionId.value || isSendingMessage) {
    return
  }

  isSendingMessage = true
  
  // Add user message to UI immediately
  const userMessage = {
    role: 'user',
    content: prompt,
    id: `user-${Date.now()}-${Math.random()}`
  }
  messages.value.push(userMessage)
  userInput.value = ''
  loading.value = true
  error.value = null
  scrollToBottom()

  try {
    const response = await sendChatbotMessage(
      practiceSessionId.value,
      currentQuestion.value.id.toString(),
      prompt
    )

    if (response.success && response.data?.message) {
      const assistantMessage = {
        role: 'assistant',
        content: response.data.message,
        id: `assistant-${Date.now()}-${Math.random()}`
      }
      messages.value.push(assistantMessage)
      
      // Update store - messages are already in local array, so just update store for persistence
      // Don't call addChatMessage as it might cause duplication - the backend already saved them
      mathPracticeStore.setChatHistory(currentQuestion.value.id.toString(), messages.value)
    } else {
      error.value = response.message || 'Failed to get response from chatbot'
      messages.value.pop() // Remove user message on error
    }
  } catch (err) {
    error.value = 'An error occurred while sending your message'
    messages.value.pop() // Remove user message on error
    console.error('Error sending message:', err)
  } finally {
    loading.value = false
    isSendingMessage = false
    scrollToBottom()
  }
}

// Handle Enter key (Shift+Enter for new line)
const handleKeyDown = (event) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    sendMessage()
  }
}

// Track current question ID to prevent duplicate loads
let currentQuestionId = null
let isLoadingHistory = false
let isSendingMessage = false

// Watch for question changes
watch(currentQuestion, (newQuestion, oldQuestion) => {
  const newQuestionId = newQuestion?.id?.toString()
  
  // Only reload if question actually changed and we're not sending a message
  if (newQuestionId && newQuestionId !== currentQuestionId && !isSendingMessage) {
    currentQuestionId = newQuestionId
    // Clear messages immediately when question changes to prevent showing old messages
    messages.value = []
    if (!isLoadingHistory) {
      loadChatHistory()
    }
  } else if (!newQuestion) {
    currentQuestionId = null
    messages.value = []
  }
}, { immediate: true })

// Watch for practice session ID (only load if question is set and session ID is new)
watch(practiceSessionId, (newId, oldId) => {
  if (newId && newId !== oldId && currentQuestion.value && !isLoadingHistory && !isSendingMessage) {
    loadChatHistory()
  }
})

onMounted(() => {
  if (currentQuestion.value && practiceSessionId.value) {
    currentQuestionId = currentQuestion.value.id.toString()
    loadChatHistory()
  }
})
</script>

<template>
  <div class="bg-white rounded-xl shadow-md flex flex-col h-full" style="min-height: 0;">
    <!-- Chatbot Header -->
    <div v-if="!hideHeader" class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <LucideMessageCircle class="w-5 h-5 text-blue-600" />
        <h3 class="font-semibold text-gray-900 text-sm">AI Math Tutor</h3>
      </div>
    </div>

    <!-- Messages Area -->
    <div class="flex-1 overflow-y-auto px-4 py-3 space-y-4" ref="messagesContainer">
      <!-- Initial message (only show if no messages) -->
      <div v-if="messages.length === 0" class="flex justify-start">
        <div class="max-w-[85%] bg-gray-100 rounded-lg px-4 py-2">
          <p class="text-sm text-gray-900">
            Hi! I'm here to help you with this math question. You can ask me:
          </p>
          <ul class="text-sm text-gray-700 mt-2 list-disc list-inside space-y-1">
            <li>How to approach this problem</li>
            <li>Step-by-step guidance</li>
          </ul>
          <p class="text-sm text-gray-700 mt-2">
            What would you like to know?
          </p>
        </div>
      </div>

      <!-- Chat Messages -->
      <div
        v-for="(message, index) in messages"
        :key="message.id || `msg-${index}`"
        :class="[
          'flex',
          message.role === 'user' ? 'justify-end' : 'justify-start'
        ]"
      >
        <div
          :class="[
            'max-w-[85%] rounded-lg px-4 py-2',
            message.role === 'user'
              ? 'bg-blue-600 text-white'
              : 'bg-gray-100 text-gray-900'
          ]"
        >
          <p class="text-sm whitespace-pre-wrap">{{ message.content }}</p>
        </div>
      </div>

      <!-- Loading indicator -->
      <div v-if="loading" class="flex justify-start">
        <div class="max-w-[85%] bg-gray-100 rounded-lg px-4 py-2">
          <div class="flex items-center gap-2">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-600"></div>
            <p class="text-sm text-gray-600">Thinking...</p>
          </div>
        </div>
      </div>

      <!-- Error message -->
      <div v-if="error" class="flex justify-start">
        <div class="max-w-[85%] bg-red-50 border border-red-200 rounded-lg px-4 py-2">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>
      </div>

      <!-- Scroll anchor -->
      <div ref="messagesEndRef"></div>
    </div>

    <!-- Input Area -->
    <div class="px-4 py-3 border-t border-gray-200">
      <div class="flex items-end gap-2">
        <textarea
          v-model="userInput"
          @keydown="handleKeyDown"
          placeholder="Ask me anything about this question..."
          rows="2"
          :disabled="loading || !currentQuestion || !practiceSessionId"
          class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
        ></textarea>
        <button
          @click="sendMessage"
          :disabled="loading || !userInput.trim() || !currentQuestion || !practiceSessionId"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2 text-sm disabled:bg-gray-400 disabled:cursor-not-allowed"
        >
          <LucideSend class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>

