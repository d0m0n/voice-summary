<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                ストレージ管理
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- ストレージ使用量表示 -->
            <div class="bg-white rounded-lg shadow-sm border mb-6 p-6" x-data="storageManagement()">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">ストレージ使用量</h3>
                    <div class="flex space-x-2">
                        <button @click="loadStats()"
                                class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-3 py-2 rounded">
                            🔄 更新
                        </button>
                        <button @click="cleanupOld()"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm px-3 py-2 rounded">
                            🧹 古いファイル削除
                        </button>
                    </div>
                </div>

                <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 使用量グラフ -->
                    <div>
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span x-text="`${stats.current_usage_formatted} / ${stats.max_storage_formatted}`"></span>
                            <span x-text="`${stats.usage_percentage}%`"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="h-4 rounded-full transition-all duration-300"
                                 :class="stats.usage_percentage >= 80 ? 'bg-red-500' : stats.usage_percentage >= 60 ? 'bg-yellow-500' : 'bg-green-500'"
                                 :style="`width: ${Math.min(stats.usage_percentage, 100)}%`"></div>
                        </div>
                        <div x-show="stats.is_warning_level" class="mt-2 text-sm text-yellow-600 bg-yellow-50 p-2 rounded">
                            ⚠️ ストレージ使用量が警告レベルに達しています
                        </div>
                        <div x-show="stats.is_storage_full" class="mt-2 text-sm text-red-600 bg-red-50 p-2 rounded">
                            🚨 ストレージがいっぱいです
                        </div>
                    </div>

                    <!-- 統計情報 -->
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">録音ファイル数:</span>
                            <span x-text="`${stats.total_recordings}個`" class="font-medium"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">平均ファイルサイズ:</span>
                            <span x-text="stats.average_file_size_formatted" class="font-medium"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">利用可能容量:</span>
                            <span x-text="stats.available_storage_formatted" class="font-medium"></span>
                        </div>
                        <div class="flex justify-between" x-show="recordingStats.oldest_recording">
                            <span class="text-gray-600">最古の録音:</span>
                            <span x-text="formatDate(recordingStats.oldest_recording)" class="text-sm"></span>
                        </div>
                        <div class="flex justify-between" x-show="recordingStats.newest_recording">
                            <span class="text-gray-600">最新の録音:</span>
                            <span x-text="formatDate(recordingStats.newest_recording)" class="text-sm"></span>
                        </div>
                    </div>
                </div>

                <!-- エラー表示 -->
                <div x-show="error" x-transition class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <span x-text="error"></span>
                </div>

                <!-- 成功メッセージ -->
                <div x-show="message" x-transition class="mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <span x-text="message"></span>
                </div>
            </div>

            <!-- 録音履歴 -->
            <div class="bg-white rounded-lg shadow-sm border" x-data="recordingManagement()">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">全録音履歴</h3>
                        <div class="flex space-x-2">
                            <button @click="loadRecordings()"
                                    class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-3 py-2 rounded">
                                🔄 更新
                            </button>
                        </div>
                    </div>
                    <div x-show="recordings.data && recordings.data.length > 0" class="flex justify-between items-center">
                        <div class="flex items-center space-x-3">
                            <label class="flex items-center">
                                <input type="checkbox"
                                       @change="toggleSelectAll()"
                                       :checked="selectedRecordings.length === recordings.data.length && recordings.data.length > 0"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">全選択</span>
                            </label>
                            <span x-show="selectedRecordings.length > 0" class="text-sm text-gray-600" x-text="`${selectedRecordings.length}件選択中`"></span>
                        </div>
                        <button x-show="selectedRecordings.length > 0"
                                @click="deleteSelectedRecordings()"
                                class="bg-red-500 hover:bg-red-600 text-white text-sm px-3 py-2 rounded">
                            選択削除
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div x-show="loading" class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
                        <p class="text-gray-500 mt-2">読み込み中...</p>
                    </div>

                    <div x-show="!loading && recordings.data && recordings.data.length === 0" class="text-center py-8 text-gray-500">
                        録音がありません
                    </div>

                    <div x-show="!loading && recordings.data && recordings.data.length > 0">
                        <!-- テーブル表示 -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">選択</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">会議</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユーザー</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">タイトル</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">時間</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">サイズ</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">録音日時</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="recording in recordings.data" :key="recording.id">
                                        <tr :class="{'bg-blue-50': selectedRecordings.includes(recording.id)}">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox"
                                                       :value="recording.id"
                                                       @change="toggleRecordingSelection(recording.id)"
                                                       :checked="selectedRecordings.includes(recording.id)"
                                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="recording.meeting ? recording.meeting.name : '-'"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="recording.user ? recording.user.name : '-'"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="recording.title"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="recording.duration_formatted || '不明'"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="recording.file_size_formatted"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(recording.recorded_at)"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button @click="deleteRecording(recording.id)"
                                                        class="text-red-600 hover:text-red-900 mr-3">
                                                    削除
                                                </button>
                                                <audio controls preload="none" class="w-32">
                                                    <source :src="recording.stream_url" :type="recording.mime_type">
                                                </audio>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- ページネーション -->
                        <div x-show="recordings.last_page > 1" class="mt-4 flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                <span x-text="`${recordings.from} - ${recordings.to} / ${recordings.total}件`"></span>
                            </div>
                            <div class="flex space-x-2">
                                <button @click="loadRecordings(recordings.current_page - 1)"
                                        :disabled="recordings.current_page <= 1"
                                        class="px-3 py-1 bg-gray-200 hover:bg-gray-300 disabled:opacity-50 rounded text-sm">
                                    前へ
                                </button>
                                <span class="px-3 py-1 text-sm" x-text="`${recordings.current_page} / ${recordings.last_page}`"></span>
                                <button @click="loadRecordings(recordings.current_page + 1)"
                                        :disabled="recordings.current_page >= recordings.last_page"
                                        class="px-3 py-1 bg-gray-200 hover:bg-gray-300 disabled:opacity-50 rounded text-sm">
                                    次へ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function storageManagement() {
        return {
            stats: {},
            recordingStats: {},
            loading: true,
            error: '',
            message: '',

            init() {
                this.loadStats();
            },

            async loadStats() {
                this.loading = true;
                this.error = '';
                try {
                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/admin/storage/stats`);

                    if (!response.ok) {
                        throw new Error(`HTTP Error: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        this.stats = data.storage_stats;
                        this.recordingStats = data.recording_stats;
                    }
                } catch (error) {
                    this.error = 'ストレージ統計の読み込みに失敗しました: ' + error.message;
                    console.error('Storage stats error:', error);
                } finally {
                    this.loading = false;
                }
            },

            async cleanupOld() {
                if (!confirm('30日以上古い録音ファイルを削除しますか？')) return;

                try {
                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/admin/storage/cleanup`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.message = data.message;
                        this.stats = data.storage_stats;
                        setTimeout(() => { this.message = ''; }, 5000);
                    } else {
                        this.error = data.message;
                    }
                } catch (error) {
                    this.error = 'クリーンアップに失敗しました: ' + error.message;
                    console.error('Cleanup error:', error);
                }
            },

            formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleString('ja-JP');
            }
        }
    }

    function recordingManagement() {
        return {
            recordings: { data: [] },
            loading: true,
            selectedRecordings: [],

            init() {
                this.loadRecordings();
            },

            async loadRecordings(page = 1) {
                this.loading = true;
                try {
                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/admin/storage/recordings?page=${page}`);

                    if (!response.ok) {
                        throw new Error(`HTTP Error: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        this.recordings = data.recordings;
                        this.selectedRecordings = [];
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
                    const response = await fetch(`${baseUrl}/admin/storage/recordings/${recordingId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.loadRecordings(this.recordings.current_page);
                    } else {
                        alert(data.message || '録音の削除に失敗しました。');
                    }
                } catch (error) {
                    console.error('録音の削除に失敗:', error);
                    alert('録音の削除中にエラーが発生しました。');
                }
            },

            toggleRecordingSelection(recordingId) {
                const index = this.selectedRecordings.indexOf(recordingId);
                if (index === -1) {
                    this.selectedRecordings.push(recordingId);
                } else {
                    this.selectedRecordings.splice(index, 1);
                }
            },

            toggleSelectAll() {
                if (this.selectedRecordings.length === this.recordings.data.length) {
                    this.selectedRecordings = [];
                } else {
                    this.selectedRecordings = this.recordings.data.map(recording => recording.id);
                }
            },

            async deleteSelectedRecordings() {
                if (this.selectedRecordings.length === 0) return;

                if (!confirm(`${this.selectedRecordings.length}個の録音を削除してもよろしいですか？`)) return;

                try {
                    const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
                    const response = await fetch(`${baseUrl}/admin/storage/recordings/delete-selected`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            recording_ids: this.selectedRecordings
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.loadRecordings(this.recordings.current_page);
                        alert(data.message);
                    } else {
                        alert(data.message || '選択された録音の削除に失敗しました。');
                    }
                } catch (error) {
                    console.error('選択された録音の削除に失敗:', error);
                    alert('録音の削除中にエラーが発生しました。');
                }
            },

            formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleString('ja-JP');
            }
        }
    }
    </script>
</x-app-layout>