<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ユーザー編集: {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                名前 *
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $user->name) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                メールアドレス *
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                権限 *
                            </label>
                            <select id="role" 
                                    name="role" 
                                    class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                    required
                                    @if($user->id === auth()->id() && $user->isAdmin() && \App\Models\User::where('role', 'admin')->count() <= 1)
                                        disabled
                                        title="最後の管理者の権限は変更できません"
                                    @endif>
                                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>管理者</option>
                                <option value="moderator" {{ old('role', $user->role) === 'moderator' ? 'selected' : '' }}>利用者</option>
                                <option value="viewer" {{ old('role', $user->role) === 'viewer' ? 'selected' : '' }}>閲覧者</option>
                            </select>
                            @if($user->id === auth()->id() && $user->isAdmin() && \App\Models\User::where('role', 'admin')->count() <= 1)
                                <input type="hidden" name="role" value="{{ $user->role }}">
                                <p class="mt-1 text-sm text-yellow-600">最後の管理者の権限は変更できません。</p>
                            @endif
                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                新しいパスワード
                                <span class="text-gray-500 text-xs">(変更する場合のみ入力)</span>
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                パスワード確認
                            </label>
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="bg-gray-50 rounded p-4 mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">アカウント情報</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">登録日:</span>
                                    <span class="text-gray-900">{{ $user->created_at->format('Y年m月d日') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">作成した会議数:</span>
                                    <span class="text-gray-900">{{ $user->meetings()->count() }}件</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('admin.users.index') }}" 
                               class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
                                キャンセル
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                                更新
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>