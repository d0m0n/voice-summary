<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                会議一覧
            </h2>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('meetings.create') }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    新規会議作成
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($meetings->count() > 0)
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($meetings as $meeting)
                                <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ $meeting->name }}
                                        </h3>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium
                                            @if($meeting->status === 'active') bg-green-100 text-green-800
                                            @elseif($meeting->status === 'completed') bg-blue-100 text-blue-800  
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ $meeting->status === 'active' ? 'アクティブ' : 
                                               ($meeting->status === 'completed' ? '完了' : 'アーカイブ') }}
                                        </span>
                                    </div>
                                    
                                    @if($meeting->description)
                                        <p class="text-gray-600 mb-3 text-sm">{{ Str::limit($meeting->description, 100) }}</p>
                                    @endif

                                    <div class="text-sm text-gray-500 mb-3">
                                        <p>作成者: {{ $meeting->creator->name }}</p>
                                        <p>作成日: {{ $meeting->created_at->format('Y/m/d H:i') }}</p>
                                        <p>言語: {{ $meeting->language }}</p>
                                        <p>要約数: {{ $meeting->summaries_count ?? $meeting->summaries->count() }}件</p>
                                    </div>

                                    <div class="flex space-x-2">
                                        <a href="{{ route('meetings.show', $meeting) }}" 
                                           class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded">
                                            表示
                                        </a>
                                        @if(auth()->user()->isAdmin())
                                            <a href="{{ route('meetings.edit', $meeting) }}" 
                                               class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-3 py-1 rounded">
                                                編集
                                            </a>
                                            <form method="POST" action="{{ route('meetings.destroy', $meeting) }}" 
                                                  class="inline-block" 
                                                  onsubmit="return confirm('この会議を削除してもよろしいですか？')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded">
                                                    削除
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $meetings->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500 mb-4">会議がまだありません。</p>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('meetings.create') }}" 
                                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    最初の会議を作成
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>