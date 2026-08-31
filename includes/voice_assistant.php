<!-- Floating Voice Assistant Trigger Button -->
<div class="voice-floating-trigger">
    <button type="button" id="voiceTriggerBtn" class="voice-btn-pulse" title="Speak to Voice Assistant">
        <span class="voice-mic-icon">🎤</span>
        <span>Voice Assistant</span>
    </button>
</div>

<!-- Interactive Voice Assistant Dialog Panel -->
<div id="voiceOverlay" class="voice-panel-overlay">
    <div class="voice-panel">
        <div class="voice-panel-header">
            <h3><span>🎤</span> GlowCart Voice Assistant</h3>
            <button type="button" id="voiceCloseBtn" class="voice-close-btn">&times;</button>
        </div>

        <div class="voice-panel-body">
            <!-- Language Selector -->
            <div class="voice-lang-select-group">
                <label for="voiceLangSelect" style="font-size: 13px; font-weight: 500;">Spoken Language:</label>
                <select id="voiceLangSelect" class="voice-lang-select">
                    <option value="en-US">English (US/India)</option>
                    <option value="hi-IN">Hindi (हिन्दी)</option>
                    <option value="or-IN">Odia (ଓଡ଼ିଆ)</option>
                </select>
            </div>

            <!-- Animated Audio Waveform -->
            <div id="voiceVisualizer" class="voice-visualizer">
                <div class="audio-bar"></div>
                <div class="audio-bar"></div>
                <div class="audio-bar"></div>
                <div class="audio-bar"></div>
                <div class="audio-bar"></div>
            </div>

            <!-- Listening / System Status -->
            <div id="voiceStatus" class="voice-status-text">
                Ready. Tap microphone to speak.
            </div>

            <!-- Live Speech Recognition Output -->
            <div class="voice-transcript-label">You said:</div>
            <div id="voiceTranscript" class="voice-transcript-box">
                <em>"Tap below and speak a command..."</em>
            </div>

            <!-- Speech Synthesis / Assistant Response -->
            <div class="voice-transcript-label" style="margin-top: 10px;">Assistant Response:</div>
            <div id="voiceResponse" class="voice-response-box">
                🔊 "How can I help you glow today?"
            </div>

            <!-- Command Help Hints -->
            <div style="margin-top: 15px; font-size: 11px; color: var(--text-muted); background: var(--surface-alt); padding: 10px; border-radius: var(--radius-sm);">
                <strong>Try saying:</strong><br>
                • <em>"Show lipstick"</em> / <em>"फाउंडेशन दिखाओ"</em> / <em>"ଲିପଷ୍ଟିକ୍ ଦେଖାଅ"</em><br>
                • <em>"Add lipstick to cart"</em> / <em>"कार्ट खोलो"</em> / <em>"Go home"</em> / <em>"Checkout"</em>
            </div>
        </div>

        <div class="voice-panel-footer">
            <button type="button" id="voiceStartBtn" class="btn btn-primary btn-block">
                🎤 Tap to Speak
            </button>
        </div>
    </div>
</div>
