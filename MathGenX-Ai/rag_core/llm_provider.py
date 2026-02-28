"""
LLM Provider Abstraction Layer
Supports switching between Gemini and Anthropic (Claude) APIs
"""
import os
from typing import Optional, Dict, Any
from dotenv import load_dotenv

load_dotenv()

# Provider types
PROVIDER_GEMINI = "gemini"
PROVIDER_ANTHROPIC = "anthropic"


class LLMProvider:
    """Abstract base class for LLM providers"""
    
    def __init__(self, model: str = None):
        self.model = model
        self.client = None
    
    def initialize(self) -> bool:
        """Initialize the provider client. Returns True if successful."""
        raise NotImplementedError
    
    def generate_content(self, prompt: str, temperature: float = 0.7, max_tokens: int = 4096) -> Dict[str, Any]:
        """
        Generate content from the LLM.
        
        Returns:
            Dict with keys:
                - 'text': str (the generated text)
                - 'finish_reason': str (reason for completion)
                - 'error': Optional[str] (error message if failed)
        """
        raise NotImplementedError


class GeminiProvider(LLMProvider):
    """Google Gemini API Provider"""
    
    def __init__(self, model: str = None):
        # Use model parameter, or read from GEMINI_MODEL env var, or fallback to default
        if model is None:
            model = os.getenv('GEMINI_MODEL', 'gemini-2.5-flash')
        super().__init__(model)
        self.api_key = os.getenv('GEMINI_API_KEY')
    
    def initialize(self) -> bool:
        """Initialize Gemini client"""
        try:
            from google import genai
            if not self.api_key:
                print("Warning: GEMINI_API_KEY not found in environment variables")
                return False
            self.client = genai.Client(api_key=self.api_key)
            return True
        except Exception as e:
            print(f"Error initializing Gemini client: {e}")
            return False
    
    def generate_content(self, prompt: str, temperature: float = 0.7, max_tokens: int = 4096) -> Dict[str, Any]:
        """Generate content using Gemini API"""
        if not self.client:
            return {
                'text': None,
                'finish_reason': None,
                'error': 'Gemini client not initialized'
            }
        
        try:
            from google import genai
            from google.genai.errors import APIError
            
            response = self.client.models.generate_content(
                model=self.model,
                contents=prompt,
                config=genai.types.GenerateContentConfig(
                    temperature=temperature,
                    max_output_tokens=max_tokens
                )
            )
            
            # Extract text and finish reason
            text = response.text if response.text else None
            finish_reason = None
            if response.candidates:
                finish_reason = response.candidates[0].finish_reason.name if response.candidates[0].finish_reason else None
            
            return {
                'text': text,
                'finish_reason': finish_reason,
                'error': None
            }
        
        except APIError as e:
            error_msg = str(e)
            # Check for transient errors: 429 (rate limit), 500 (server error), 503 (service unavailable)
            # Also check status_code if available in the exception
            status_code = getattr(e, 'status_code', None) if hasattr(e, 'status_code') else None
            if status_code is None:
                # Try to extract from error message
                if "503" in error_msg or "500" in error_msg:
                    status_code = 503 if "503" in error_msg else 500
                elif "429" in error_msg or "rate limit" in error_msg.lower() or "quota" in error_msg.lower():
                    status_code = 429
            
            is_transient = (
                status_code == 429 or  # Rate limit
                status_code == 500 or  # Internal server error
                status_code == 503     # Service unavailable
            ) if status_code else False
            
            return {
                'text': None,
                'finish_reason': None,
                'error': error_msg,
                'is_transient': is_transient,
                'status_code': status_code
            }
        except Exception as e:
            return {
                'text': None,
                'finish_reason': None,
                'error': str(e)
            }


class AnthropicProvider(LLMProvider):
    """Anthropic Claude API Provider"""
    
    def __init__(self, model: str = None):
        # Use model parameter, or read from ANTHROPIC_MODEL env var, or fallback to default
        if model is None:
            model = os.getenv('ANTHROPIC_MODEL', 'claude-sonnet-4-5')
        super().__init__(model)
        self.api_key = os.getenv('ANTHROPIC_API_KEY')
        self.api_version = os.getenv('ANTHROPIC_VERSION', '2023-06-01')
    
    def initialize(self) -> bool:
        """Initialize Anthropic client"""
        try:
            import anthropic
            if not self.api_key:
                print("Warning: ANTHROPIC_API_KEY not found in environment variables")
                return False
            self.client = anthropic.Anthropic(api_key=self.api_key)
            return True
        except ImportError:
            print("Error: anthropic package not installed. Run: pip install anthropic")
            return False
        except Exception as e:
            print(f"Error initializing Anthropic client: {e}")
            return False
    
    def generate_content(self, prompt: str, temperature: float = 0.7, max_tokens: int = 4096) -> Dict[str, Any]:
        """Generate content using Anthropic API"""
        if not self.client:
            return {
                'text': None,
                'finish_reason': None,
                'error': 'Anthropic client not initialized'
            }
        
        try:
            import anthropic
            
            response = self.client.messages.create(
                model=self.model,
                max_tokens=max_tokens,
                temperature=temperature,
                messages=[
                    {
                        "role": "user",
                        "content": prompt
                    }
                ]
            )
            
            # Extract text from response
            text = None
            finish_reason = None
            
            if response.content:
                # Anthropic returns content as a list of content blocks
                text_parts = []
                for block in response.content:
                    if block.type == 'text':
                        text_parts.append(block.text)
                text = '\n'.join(text_parts) if text_parts else None
            
            finish_reason = response.stop_reason if hasattr(response, 'stop_reason') else None
            
            return {
                'text': text,
                'finish_reason': finish_reason,
                'error': None
            }
        
        except anthropic.APIError as e:
            error_msg = str(e)
            # Check for transient errors (rate limits, server errors)
            is_transient = (
                e.status_code >= 500 or  # Server errors
                e.status_code == 429    # Rate limit
            )
            return {
                'text': None,
                'finish_reason': None,
                'error': error_msg,
                'is_transient': is_transient,
                'status_code': e.status_code if hasattr(e, 'status_code') else None
            }
        except Exception as e:
            return {
                'text': None,
                'finish_reason': None,
                'error': str(e)
            }


def get_llm_provider(provider_name: Optional[str] = None, model: Optional[str] = None) -> Optional[LLMProvider]:
    """
    Factory function to get the appropriate LLM provider.
    Auto-detects available provider if specified one fails.
    
    Args:
        provider_name: 'gemini' or 'anthropic'. If None, reads from LLM_PROVIDER env var.
        model: Model name to use. If None, uses default for the provider.
    
    Returns:
        LLMProvider instance or None if both providers fail
    """
    # Get provider from env if not specified
    if provider_name is None:
        provider_name = os.getenv('LLM_PROVIDER', PROVIDER_GEMINI).lower()
    
    # Try the specified provider first
    if provider_name == PROVIDER_GEMINI:
        # Pass model if provided, otherwise let __init__ read from GEMINI_MODEL env var
        provider = GeminiProvider(model=model)
        if provider.initialize():
            return provider
        # If Gemini fails, try Anthropic as fallback
        print(f"⚠️  Gemini provider failed, trying Anthropic as fallback...")
        provider = AnthropicProvider(model=model)
        if provider.initialize():
            return provider
    
    elif provider_name == PROVIDER_ANTHROPIC:
        # Pass model if provided, otherwise let __init__ read from ANTHROPIC_MODEL env var
        provider = AnthropicProvider(model=model)
        if provider.initialize():
            return provider
        # If Anthropic fails, try Gemini as fallback
        print(f"⚠️  Anthropic provider failed, trying Gemini as fallback...")
        provider = GeminiProvider(model=model)
        if provider.initialize():
            return provider
    
    else:
        print(f"Unknown provider: {provider_name}. Supported: {PROVIDER_GEMINI}, {PROVIDER_ANTHROPIC}")
        return None
    
    # Both providers failed
    print(f"❌ Failed to initialize both providers. Check your API keys in .env file")
    return None


# Global provider instance (initialized on first use)
_provider_instance: Optional[LLMProvider] = None


def get_provider() -> Optional[LLMProvider]:
    """Get or create the global LLM provider instance"""
    global _provider_instance
    if _provider_instance is None:
        _provider_instance = get_llm_provider()
    return _provider_instance


def reset_provider():
    """Reset the global provider instance (useful for testing or switching providers)"""
    global _provider_instance
    _provider_instance = None

