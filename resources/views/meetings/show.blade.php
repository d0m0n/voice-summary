<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $meeting->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $meeting->description }}
                </p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    @if($meeting->status === 'active') bg-green-100 text-green-800
                    @elseif($meeting->status === 'completed') bg-blue-100 text-blue-800  
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ $meeting->status === 'active' ? 'アクティブ' : 
                       ($meeting->status === 'completed' ? '完了' : 'アーカイブ') }}
                </span>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('meetings.edit', $meeting) }}" 
                       class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm px-3 py-1 rounded">
                        編集
                    </a>
                @endif
                <a href="{{ route('meetings.index') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white text-sm px-3 py-1 rounded">
                    一覧に戻る
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- 音声認識・録音コントロール -->
            @if(auth()->user()->canUseVoiceRecognition())
            <div class="bg-white rounded-lg shadow-sm border p-6" x-data="voiceRecognitionApp({{ $meeting->id }}, '{{ $meeting->language }}')">
                <h3 class="text-lg font-semibold mb-4">音声認識コントロール</h3>
                
                <!-- ブラウザサポート確認 -->
                <div x-show="!isSupported" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    お使いのブラウザは音声認識に対応していません。Chrome、Safari、Edgeをお使いください。
                </div>

                <!-- 言語選択 -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">認識言語</label>
                    <select x-model="language" @change="updateLanguage()" 
                            class="border border-gray-300 rounded-md px-3 py-2">
                        <option value="ja-JP">日本語</option>
                        <option value="en-US">English (US)</option>
                        <option value="en-GB">English (UK)</option>
                        <option value="zh-CN">中文 (简体)</option>
                        <option value="ko-KR">한국어</option>
                        <option value="id-ID">Bahasa Indonesia</option>
                    </select>
                </div>

                <!-- コントロールボタン -->
                <div class="flex flex-wrap gap-4 mb-4">
                    <button @click="toggleRecording()"
                            :disabled="!isSupported"
                            class="px-6 py-3 rounded-lg font-medium transition-colors"
                            :class="isRecording ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white'">
                        <span x-show="!isRecording">🎤 音声認識開始</span>
                        <span x-show="isRecording">🛑 音声認識停止</span>
                    </button>

                    <button @click="generateManualSummary()"
                            :disabled="!accumulatedText.trim()"
                            class="px-6 py-3 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white rounded-lg font-medium">
                        📝 手動要約
                    </button>

                    <button @click="clearText()"
                            class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium">
                        🗑️ テキストクリア
                    </button>
                </div>

                <!-- 認識状態表示 -->
                <div class="mb-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full"
                             :class="isRecording ? 'bg-red-500 animate-pulse' : 'bg-gray-300'"></div>
                        <span class="text-sm font-medium" x-text="isRecording ? '認識中...' : '停止中'"></span>
                        <span x-show="isRecording && currentText" class="text-xs text-gray-500">
                            (リアルタイム認識中)
                        </span>
                    </div>
                </div>

                <!-- リアルタイム認識テキスト -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">リアルタイム認識テキスト</label>
                    <div class="bg-gray-50 border rounded-lg p-4 min-h-[60px]">
                        <p class="text-gray-600 italic" x-text="currentText || 'ここにリアルタイムで認識されたテキストが表示されます'"></p>
                    </div>
                </div>

                <!-- 確定テキスト -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">確定テキスト</label>
                    <textarea x-model="accumulatedText"
                              class="w-full border border-gray-300 rounded-lg p-4 min-h-[120px]"
                              placeholder="ここに確定されたテキストが蓄積されます"
                              readonly></textarea>
                </div>

                <!-- エラー表示 -->
                <div x-show="error" x-transition class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <span x-text="error"></span>
                </div>
            </div>
            @endif

            <!-- 録音コントロール（管理者のみ） -->
            @if(auth()->user()->isAdmin())
            <div class="bg-white rounded-lg shadow-sm border mb-6 p-6" x-data="audioRecordingApp({{ $meeting->id }})">
                <h3 class="text-lg font-semibold mb-4">録音コントロール</h3>

                <!-- 録音ボタンとステータス -->
                <div class="flex flex-wrap gap-4 mb-4">
                    <button @click="toggleRecording()"
                            :disabled="!isSupported || isStorageFull"
                            class="px-6 py-3 rounded-lg font-medium transition-colors"
                            :class="isRecording ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-purple-500 hover:bg-purple-600 text-white disabled:bg-gray-300'">
                        <span x-show="!isRecording">🔴 録音開始</span>
                        <span x-show="isRecording">⏹️ 録音停止</span>
                    </button>

                    <button @click="saveRecording()"
                            :disabled="!hasRecording || isSaving"
                            class="px-6 py-3 bg-green-500 hover:bg-green-600 disabled:bg-gray-300 text-white rounded-lg font-medium">
                        <span x-show="!isSaving">💾 録音保存</span>
                        <span x-show="isSaving">保存中...</span>
                    </button>

                    <button @click="discardRecording()"
                            :disabled="!hasRecording"
                            class="px-6 py-3 bg-red-500 hover:bg-red-600 disabled:bg-gray-300 text-white rounded-lg font-medium">
                        🗑️ 録音削除
                    </button>
                </div>

                <!-- 録音状態表示 -->
                <div class="mb-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 rounded-full"
                                 :class="isRecording ? 'bg-red-500 animate-pulse' : 'bg-gray-300'"></div>
                            <span class="text-sm font-medium" x-text="isRecording ? '録音中...' : '停止中'"></span>
                        </div>

                        <div x-show="isRecording" class="text-sm text-gray-600">
                            <span x-text="recordingTime"></span>
                        </div>

                        <div x-show="isRecording" class="text-sm text-gray-600">
                            <span x-text="currentFileSize"></span>
                        </div>
                    </div>

                    <!-- 音声レベルメーター -->
                    <div x-show="isRecording" class="mt-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500">音量:</span>
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full bg-green-500 transition-all duration-100"
                                     :style="`width: ${audioLevel}%`"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- エラー・警告表示 -->
                <div x-show="error" x-transition class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <span x-text="error"></span>
                </div>

                <div x-show="!isSupported" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    お使いのブラウザは録音に対応していません。Chrome、Safari、Edgeをお使いください。
                </div>

                <div x-show="isStorageFull" class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    ストレージ容量が不足しています。録音を開始できません。
                </div>
            </div>
            @endif

            <!-- 要約履歴 -->
            <div class="bg-white rounded-lg shadow-sm border" x-data="summaryDisplay({{ $meeting->id }})">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold">要約履歴</h3>
                        <div class="flex space-x-2">
                            @if(auth()->user()->canUseVoiceRecognition())
                            <a href="{{ route('meetings.export.csv', $meeting) }}"
                               class="bg-green-500 hover:bg-green-600 text-white text-sm px-3 py-2 rounded">
                                📊 CSV
                            </a>
                            <a href="{{ route('meetings.export.txt', $meeting) }}"
                               class="bg-purple-500 hover:bg-purple-600 text-white text-sm px-3 py-2 rounded">
                                📄 TXT
                            </a>
                            @endif
                            @if(auth()->user()->isViewer())
                            <!-- 自動更新トグルボタン（閲覧者のみ） -->
                            <button @click="toggleAutoRefresh()"
                                    class="text-sm px-3 py-2 rounded transition-colors"
                                    :class="autoRefreshEnabled ? 'bg-green-500 hover:bg-green-600 text-white' : 'bg-gray-500 hover:bg-gray-600 text-white'">
                                <span x-show="!autoRefreshEnabled">🔄 自動更新</span>
                                <span x-show="autoRefreshEnabled">⏸️ 更新停止</span>
                            </button>
                            @else
                            <!-- 手動更新ボタン（閲覧者以外） -->
                            <button @click="loadSummaries()"
                                    class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-3 py-2 rounded">
                                🔄 更新
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    <div x-show="loading" class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
                        <p class="text-gray-500 mt-2">読み込み中...</p>
                    </div>

                    <div x-show="!loading && summaries.length === 0" class="text-center py-8 text-gray-500">
                        まだ要約がありません
                    </div>

                    <div x-show="!loading && summaries.length > 0" class="space-y-4">
                        <template x-for="summary in summaries" :key="summary.id">
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center space-x-2">
                                        <span x-text="summary.type === 'auto' ? '🤖 自動要約' : '👤 手動要約'"
                                              class="text-sm font-medium px-2 py-1 rounded"
                                              :class="summary.type === 'auto' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'"></span>
                                        <span class="text-sm text-gray-500" x-text="formatDate(summary.created_at)"></span>
                                    </div>
                                    @if(auth()->user()->isAdmin())
                                    <button @click="deleteSummary(summary.id)" 
                                            class="text-red-500 hover:text-red-700 text-sm">
                                        🗑️ 削除
                                    </button>
                                    @endif
                                </div>
                                
                                <div class="mb-3">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2" x-text="summary.user ? `${summary.user.name}による要約:` : '要約:'"></h4>
                                    <p class="text-gray-900 leading-relaxed" x-text="summary.summary"></p>
                                </div>
                                
                                <details class="mt-3">
                                    <summary class="text-sm text-gray-600 cursor-pointer hover:text-gray-800">
                                        元のテキストを表示
                                    </summary>
                                    <div class="mt-2 p-3 bg-gray-50 rounded text-sm text-gray-700">
                                        <p x-text="summary.original_text"></p>
                                    </div>
                                </details>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- 録音履歴（利用者以上） -->
            @if(auth()->user()->canUseVoiceRecognition())
            <div class="bg-white rounded-lg shadow-sm border" x-data="recordingDisplay({{ $meeting->id }})">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold">録音履歴</h3>
                        <button @click="loadRecordings()"
                                class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-3 py-2 rounded">
                            🔄 更新
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div x-show="loading" class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
                        <p class="text-gray-500 mt-2">読み込み中...</p>
                    </div>

                    <div x-show="!loading && recordings.length === 0" class="text-center py-8 text-gray-500">
                        まだ録音がありません
                    </div>

                    <div x-show="!loading && recordings.length > 0" class="space-y-4">
                        <template x-for="recording in recordings" :key="recording.id">
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium px-2 py-1 rounded bg-purple-100 text-purple-800">
                                            🎵 録音
                                        </span>
                                        <span class="text-sm text-gray-500" x-text="formatDate(recording.recorded_at)"></span>
                                        <span class="text-xs text-gray-400" x-text="recording.user ? `by ${recording.user.name}` : ''"></span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a :href="`${baseUrl}/recordings/${recording.id}/download`"
                                           class="text-blue-500 hover:text-blue-700 text-sm">
                                            📥 ダウンロード
                                        </a>
                                        @if(auth()->user()->isAdmin() || auth()->user()->canUseVoiceRecognition())
                                        <button @click="deleteRecording(recording.id)"
                                                x-show="canDelete(recording)"
                                                class="text-red-500 hover:text-red-700 text-sm">
                                            🗑️ 削除
                                        </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2" x-text="recording.title"></h4>
                                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                                        <span x-text="`時間: ${recording.duration_formatted || '不明'}`"></span>
                                        <span x-text="`サイズ: ${recording.file_size_formatted}`"></span>
                                    </div>
                                </div>

                                <!-- 音声プレーヤー -->
                                <div class="mt-3">
                                    <audio controls preload="none" class="w-full max-w-md">
                                        <source :src="recording.stream_url" :type="recording.mime_type">
                                        <!-- Fallback for Safari if original format doesn't work -->
                                        <source :src="recording.stream_url" type="audio/mpeg">
                                        <source :src="recording.stream_url" type="audio/mp4">
                                        お使いのブラウザは音声再生に対応していません。
                                    </audio>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <script>
    function voiceRecognitionApp(meetingId, initialLanguage) {
        return {
            meetingId: meetingId,
            language: initialLanguage,
            isRecording: false,
            isSupported: false,
            currentText: '',
            accumulatedText: '',
            error: '',
            voiceManager: null,

            init() {
                this.isSupported = ('webkitSpeechRecognition' in window) || ('SpeechRecognition' in window);
                
                if (this.isSupported) {
                    this.initVoiceRecognition();
                }
            },

            initVoiceRecognition() {
                this.voiceManager = new VoiceRecognitionManager({
                    language: this.language,
                    meetingId: this.meetingId,
                    onResult: (current, accumulated) => {
                        this.currentText = current;
                        this.accumulatedText = accumulated;
                    },
                    onFinalResult: (text) => {
                        this.accumulatedText = text;
                    },
                    onSummaryGenerated: (summary, type) => {
                        this.$dispatch('summary-generated', { summary, type });
                        this.accumulatedText = ''; // 自動要約後はクリア
                    },
                    onError: (error) => {
                        this.error = error;
                    },
                    onStateChange: (state) => {
                        this.isRecording = state === 'recording';
                    }
                });
            },

            toggleRecording() {
                if (this.isRecording) {
                    this.voiceManager.stop();
                } else {
                    this.error = '';
                    this.voiceManager.start();
                }
            },

            updateLanguage() {
                if (this.voiceManager) {
                    this.voiceManager.setLanguage(this.language);
                }
            },

            generateManualSummary() {
                if (this.voiceManager) {
                    this.voiceManager.generateManualSummary();
                }
            },

            clearText() {
                this.currentText = '';
                this.accumulatedText = '';
                this.error = '';
                if (this.voiceManager) {
                    this.voiceManager.clearText();
                }
            }
        }
    }

    function summaryDisplay(meetingId) {
        return {
            meetingId: meetingId,
            summaries: [],
            loading: true,
            autoRefreshEnabled: false,
            autoRefreshInterval: null,
            refreshIntervalSeconds: 30, // 30秒間隔でサーバー負荷を軽減

            init() {
                this.loadSummaries();

                // 要約生成イベントをリッスン
                this.$watch('$store', () => {
                    // 新しい要約が生成されたら再読み込み
                    this.loadSummaries();
                });

                // カスタムイベントをリッスン
                window.addEventListener('summary-generated', () => {
                    setTimeout(() => this.loadSummaries(), 1000);
                });

                // ページを離れる時に自動更新を停止
                window.addEventListener('beforeunload', () => {
                    this.stopAutoRefresh();
                });
            },

            async loadSummaries() {
                this.loading = true;
                try {
                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/meetings/${this.meetingId}/summaries`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP Error: ${response.status} - ${response.statusText}`);
                    }

                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('非JSONレスポンス:', text);
                        throw new Error('サーバーから不正なレスポンスが返されました');
                    }

                    const data = await response.json();
                    
                    if (data.success) {
                        this.summaries = data.summaries;
                    }
                } catch (error) {
                    console.error('要約の読み込みに失敗:', error);
                } finally {
                    this.loading = false;
                }
            },

            async deleteSummary(summaryId) {
                if (!confirm('この要約を削除してもよろしいですか？')) return;

                try {
                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/summaries/${summaryId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.loadSummaries();
                    }
                } catch (error) {
                    console.error('要約の削除に失敗:', error);
                }
            },

            toggleAutoRefresh() {
                if (this.autoRefreshEnabled) {
                    this.stopAutoRefresh();
                } else {
                    this.startAutoRefresh();
                }
            },

            startAutoRefresh() {
                this.autoRefreshEnabled = true;
                this.autoRefreshInterval = setInterval(() => {
                    // ローディング中でなければ更新を実行
                    if (!this.loading) {
                        this.loadSummaries();
                    }
                }, this.refreshIntervalSeconds * 1000);
                console.log(`自動更新を開始しました（${this.refreshIntervalSeconds}秒間隔）`);
            },

            stopAutoRefresh() {
                this.autoRefreshEnabled = false;
                if (this.autoRefreshInterval) {
                    clearInterval(this.autoRefreshInterval);
                    this.autoRefreshInterval = null;
                }
                console.log('自動更新を停止しました');
            },

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString('ja-JP');
            }
        }
    }

    function audioRecordingApp(meetingId) {
        return {
            meetingId: meetingId,
            isRecording: false,
            isSupported: false,
            isStorageFull: false,
            hasRecording: false,
            isSaving: false,
            recordingTime: '00:00:00',
            currentFileSize: '0 KB',
            audioLevel: 0,
            error: '',

            mediaRecorder: null,
            recordedChunks: [],
            audioContext: null,
            analyser: null,
            microphone: null,
            startTime: null,
            recordingTimer: null,
            storageConfig: null,

            init() {
                this.isSupported = navigator.mediaDevices && navigator.mediaDevices.getUserMedia;
                this.checkStorageCapacity();
            },

            async checkStorageCapacity() {
                try {
                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/recordings/check-capacity`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ estimated_size: 0 })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.isStorageFull = !data.can_store;
                        this.storageConfig = {
                            maxSize: data.max_recording_size,
                            maxTime: data.max_recording_time
                        };
                    }
                } catch (error) {
                    console.error('容量チェックエラー:', error);
                }
            },

            async toggleRecording() {
                if (this.isRecording) {
                    this.stopRecording();
                } else {
                    await this.startRecording();
                }
            },

            async startRecording() {
                try {
                    this.error = '';

                    const stream = await navigator.mediaDevices.getUserMedia({
                        audio: {
                            sampleRate: 16000,
                            channelCount: 1,
                            echoCancellation: true,
                            noiseSuppression: true
                        }
                    });

                    // Setup audio context for level monitoring
                    this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    this.analyser = this.audioContext.createAnalyser();
                    this.microphone = this.audioContext.createMediaStreamSource(stream);
                    this.microphone.connect(this.analyser);
                    this.analyser.fftSize = 256;

                    // Setup MediaRecorder with best compatible format
                    let mimeType = 'audio/webm';
                    if (MediaRecorder.isTypeSupported('audio/mp4')) {
                        mimeType = 'audio/mp4';
                    } else if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
                        mimeType = 'audio/webm;codecs=opus';
                    }

                    this.mediaRecorder = new MediaRecorder(stream, {
                        mimeType: mimeType
                    });
                    this.recordingMimeType = mimeType;

                    this.recordedChunks = [];
                    this.mediaRecorder.ondataavailable = (event) => {
                        if (event.data.size > 0) {
                            this.recordedChunks.push(event.data);
                            this.updateFileSize();
                        }
                    };

                    this.mediaRecorder.onstop = () => {
                        this.hasRecording = this.recordedChunks.length > 0;
                        stream.getTracks().forEach(track => track.stop());
                    };

                    // Start recording
                    this.mediaRecorder.start(1000); // Record in 1-second chunks
                    this.isRecording = true;
                    this.startTime = Date.now();
                    this.startTimer();
                    this.startAudioLevelMonitoring();

                    // Auto-stop after max recording time
                    if (this.storageConfig?.maxTime) {
                        setTimeout(() => {
                            if (this.isRecording) {
                                this.stopRecording();
                                this.error = '録音時間の上限に達しました。';
                            }
                        }, this.storageConfig.maxTime * 1000);
                    }

                } catch (error) {
                    this.error = '録音の開始に失敗しました: ' + error.message;
                    console.error('録音開始エラー:', error);
                }
            },

            stopRecording() {
                if (this.mediaRecorder && this.isRecording) {
                    this.mediaRecorder.stop();
                    this.isRecording = false;
                    this.clearTimer();
                    this.stopAudioLevelMonitoring();
                }
            },

            startTimer() {
                this.recordingTimer = setInterval(() => {
                    const elapsed = Date.now() - this.startTime;
                    this.recordingTime = this.formatDuration(Math.floor(elapsed / 1000));
                }, 1000);
            },

            clearTimer() {
                if (this.recordingTimer) {
                    clearInterval(this.recordingTimer);
                    this.recordingTimer = null;
                }
            },

            startAudioLevelMonitoring() {
                const dataArray = new Uint8Array(this.analyser.frequencyBinCount);

                const updateLevel = () => {
                    if (!this.isRecording) return;

                    this.analyser.getByteFrequencyData(dataArray);
                    const average = dataArray.reduce((a, b) => a + b) / dataArray.length;
                    this.audioLevel = Math.min(100, (average / 255) * 100);

                    requestAnimationFrame(updateLevel);
                };

                updateLevel();
            },

            stopAudioLevelMonitoring() {
                this.audioLevel = 0;
                if (this.audioContext) {
                    this.audioContext.close();
                    this.audioContext = null;
                }
            },

            updateFileSize() {
                const totalSize = this.recordedChunks.reduce((total, chunk) => total + chunk.size, 0);
                this.currentFileSize = this.formatBytes(totalSize);

                // Check size limit
                if (this.storageConfig?.maxSize && totalSize > this.storageConfig.maxSize) {
                    this.stopRecording();
                    this.error = 'ファイルサイズの上限に達しました。';
                }
            },

            async saveRecording() {
                if (!this.hasRecording || this.isSaving) return;

                this.isSaving = true;
                this.error = '';

                try {
                    const mimeType = this.recordingMimeType || 'audio/webm';
                    const extension = mimeType.includes('mp4') ? 'mp4' : 'webm';

                    const blob = new Blob(this.recordedChunks, { type: mimeType });
                    // Calculate duration from recording time
                    const duration = this.startTime ? Math.floor((Date.now() - this.startTime) / 1000) : 0;

                    const formData = new FormData();
                    formData.append('audio_data', blob, `recording_${Date.now()}.${extension}`);
                    formData.append('duration', duration);
                    formData.append('title', `録音 - ${new Date().toLocaleString('ja-JP')}`);

                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/meetings/${this.meetingId}/recordings`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.discardRecording();
                        // Trigger recording list reload
                        window.dispatchEvent(new CustomEvent('recording-saved'));
                        // Update storage stats
                        window.dispatchEvent(new CustomEvent('storage-updated'));
                    } else {
                        this.error = data.message || '録音の保存に失敗しました。';
                    }

                } catch (error) {
                    this.error = '録音の保存中にエラーが発生しました: ' + error.message;
                    console.error('録音保存エラー:', error);
                } finally {
                    this.isSaving = false;
                }
            },

            discardRecording() {
                this.recordedChunks = [];
                this.hasRecording = false;
                this.recordingTime = '00:00:00';
                this.currentFileSize = '0 KB';
                this.error = '';
            },

            formatDuration(seconds) {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            },

            formatBytes(bytes) {
                const units = ['B', 'KB', 'MB', 'GB'];
                let size = bytes;
                let unitIndex = 0;

                while (size >= 1024 && unitIndex < units.length - 1) {
                    size /= 1024;
                    unitIndex++;
                }

                return `${size.toFixed(1)} ${units[unitIndex]}`;
            }
        }
    }

    function recordingDisplay(meetingId) {
        return {
            meetingId: meetingId,
            recordings: [],
            loading: true,
            baseUrl: document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '',

            init() {
                this.loadRecordings();

                // Listen for recording saved events
                window.addEventListener('recording-saved', () => {
                    setTimeout(() => this.loadRecordings(), 1000);
                });
            },

            async loadRecordings() {
                this.loading = true;
                try {
                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/meetings/${this.meetingId}/recordings`);

                    if (!response.ok) {
                        throw new Error(`HTTP Error: ${response.status} - ${response.statusText}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        this.recordings = data.recordings;
                    }
                } catch (error) {
                    console.error('録音の読み込みに失敗:', error);
                } finally {
                    this.loading = false;
                }
            },

            async deleteRecording(recordingId) {
                if (!confirm('この録音を削除してもよろしいですか？')) return;

                try {
                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/recordings/${recordingId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.loadRecordings();
                        // Update storage stats
                        window.dispatchEvent(new CustomEvent('storage-updated'));
                    } else {
                        alert(data.message || '録音の削除に失敗しました。');
                    }
                } catch (error) {
                    console.error('録音の削除に失敗:', error);
                    alert('録音の削除中にエラーが発生しました。');
                }
            },

            canDelete(recording) {
                // Check if user can delete this recording (admin or owner)
                const currentUserId = {{ auth()->id() }};
                const isAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};
                return isAdmin || (recording.user && recording.user.id === currentUserId);
            },

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString('ja-JP');
            }
        }
    }

    function storageDisplay() {
        return {
            stats: {
                current_usage_formatted: '0 B',
                max_storage_formatted: '0 B',
                usage_percentage: 0,
                total_recordings: 0,
                average_file_size_formatted: '0 B',
                is_warning_level: false,
                is_storage_full: false
            },
            loading: true,

            init() {
                this.loadStorageStats();

                // Listen for storage update events
                window.addEventListener('storage-updated', () => {
                    setTimeout(() => this.loadStorageStats(), 500);
                });
            },

            async loadStorageStats() {
                this.loading = true;
                try {
                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/storage/stats`);

                    if (!response.ok) {
                        throw new Error(`HTTP Error: ${response.status} - ${response.statusText}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        this.stats = data.storage_stats;
                    }
                } catch (error) {
                    console.error('ストレージ統計の読み込みに失敗:', error);
                } finally {
                    this.loading = false;
                }
            }
        }
    }
    </script>
</x-app-layout>