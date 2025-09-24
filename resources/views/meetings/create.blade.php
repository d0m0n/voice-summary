<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            新規会議作成
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('meetings.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                会議名 *
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                会議の説明
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="3"
                                      class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="会議の目的や内容について簡単に説明してください">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                                音声認識言語 *
                            </label>
                            <select id="language" 
                                    name="language" 
                                    class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                    required>
                                <option value="ja-JP" {{ old('language', 'ja-JP') === 'ja-JP' ? 'selected' : '' }}>日本語</option>
                                <option value="en-US" {{ old('language') === 'en-US' ? 'selected' : '' }}>English (US)</option>
                                <option value="en-GB" {{ old('language') === 'en-GB' ? 'selected' : '' }}>English (UK)</option>
                                <option value="zh-CN" {{ old('language') === 'zh-CN' ? 'selected' : '' }}>中文 (简体)</option>
                                <option value="ko-KR" {{ old('language') === 'ko-KR' ? 'selected' : '' }}>한국어</option>
                                <option value="id-ID" {{ old('language') === 'id-ID' ? 'selected' : '' }}>Bahasa Indonesia</option>
                            </select>
                            @error('language')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('meetings.index') }}" 
                               class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
                                キャンセル
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                                会議を作成
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>