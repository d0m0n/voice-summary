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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- 音声認識コントロール -->
            @if(auth()->user()->canUseVoiceRecognition())
            <div class="bg-white rounded-lg shadow-sm border mb-6 p-6" x-data="voiceRecognitionApp({{ $meeting->id }}, '{{ $meeting->language }}')">
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
                <div class="flex space-x-4 mb-4">
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

            <!-- 要約履歴 -->
            <div class="bg-white rounded-lg shadow-sm border" x-data="summaryDisplay({{ $meeting->id }})">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold">要約履歴</h3>
                        <button @click="loadSummaries()" 
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
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">要約:</h4>
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

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString('ja-JP');
            }
        }
    }
    </script>
</x-app-layout>