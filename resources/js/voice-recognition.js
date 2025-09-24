class VoiceRecognitionManager {
    constructor(options = {}) {
        this.recognition = null;
        this.isRecording = false;
        this.accumulatedText = '';
        this.currentText = '';
        this.silenceTimeout = null;
        this.silenceDelay = options.silenceDelay || 3000; // 3秒
        this.language = options.language || 'ja-JP';
        this.meetingId = options.meetingId;
        this.autoSummary = options.autoSummary !== false;

        // コールバック関数
        this.onResult = options.onResult || (() => {});
        this.onFinalResult = options.onFinalResult || (() => {});
        this.onSummaryGenerated = options.onSummaryGenerated || (() => {});
        this.onError = options.onError || (() => {});
        this.onStateChange = options.onStateChange || (() => {});

        this.initializeRecognition();
    }

    initializeRecognition() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            throw new Error('お使いのブラウザは音声認識に対応していません。Chrome、Safari、Edgeをお使いください。');
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognition = new SpeechRecognition();

        this.recognition.continuous = true;
        this.recognition.interimResults = true;
        this.recognition.lang = this.language;

        this.setupEventListeners();
    }

    setupEventListeners() {
        this.recognition.onstart = () => {
            this.isRecording = true;
            this.onStateChange('recording');
            console.log('音声認識開始');
        };

        this.recognition.onresult = (event) => {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript;
                } else {
                    interimTranscript += transcript;
                }
            }

            this.currentText = interimTranscript;
            this.onResult(interimTranscript, this.accumulatedText + finalTranscript);

            if (finalTranscript) {
                this.accumulatedText += finalTranscript;
                this.onFinalResult(this.accumulatedText);
                this.resetSilenceTimer();
            }
        };

        this.recognition.onerror = (event) => {
            console.error('音声認識エラー:', event.error);
            this.onError(`音声認識エラー: ${event.error}`);
            
            if (event.error === 'not-allowed') {
                this.onError('マイクの使用が許可されていません。ブラウザの設定を確認してください。');
            }
        };

        this.recognition.onend = () => {
            this.isRecording = false;
            this.onStateChange('stopped');
            console.log('音声認識終了');
        };
    }

    start() {
        if (this.isRecording) return;

        try {
            this.accumulatedText = '';
            this.currentText = '';
            this.recognition.start();
        } catch (error) {
            this.onError('音声認識の開始に失敗しました: ' + error.message);
        }
    }

    stop() {
        if (!this.isRecording) return;

        this.recognition.stop();
        this.clearSilenceTimer();
    }

    restart() {
        this.stop();
        setTimeout(() => this.start(), 100);
    }

    resetSilenceTimer() {
        this.clearSilenceTimer();
        
        if (this.autoSummary && this.accumulatedText.trim().length > 10) {
            this.silenceTimeout = setTimeout(() => {
                this.generateAutoSummary();
            }, this.silenceDelay);
        }
    }

    clearSilenceTimer() {
        if (this.silenceTimeout) {
            clearTimeout(this.silenceTimeout);
            this.silenceTimeout = null;
        }
    }

    async generateAutoSummary() {
        if (!this.accumulatedText.trim() || !this.meetingId) return;

        try {
            const response = await fetch(`/meetings/${this.meetingId}/summaries`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    text: this.accumulatedText,
                    type: 'auto'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.onSummaryGenerated(data.summary, 'auto');
                // 要約後にテキストをリセット
                this.accumulatedText = '';
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('自動要約エラー:', error);
            this.onError('自動要約の生成に失敗しました: ' + error.message);
        }
    }

    async generateManualSummary() {
        if (!this.accumulatedText.trim() || !this.meetingId) {
            this.onError('要約するテキストがありません。');
            return;
        }

        try {
            const response = await fetch(`/meetings/${this.meetingId}/summaries`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    text: this.accumulatedText,
                    type: 'manual'
                })
            });

            const data = await response.json();

            if (data.success) {
                this.onSummaryGenerated(data.summary, 'manual');
                // 手動要約後はテキストを保持
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('手動要約エラー:', error);
            this.onError('手動要約の生成に失敗しました: ' + error.message);
        }
    }

    setLanguage(language) {
        this.language = language;
        if (this.recognition) {
            this.recognition.lang = language;
        }
    }

    clearText() {
        this.accumulatedText = '';
        this.currentText = '';
        this.clearSilenceTimer();
    }

    getAccumulatedText() {
        return this.accumulatedText;
    }

    getCurrentText() {
        return this.currentText;
    }

    isSupported() {
        return ('webkitSpeechRecognition' in window) || ('SpeechRecognition' in window);
    }
}

window.VoiceRecognitionManager = VoiceRecognitionManager;