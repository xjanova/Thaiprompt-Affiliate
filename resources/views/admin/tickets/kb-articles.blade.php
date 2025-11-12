@extends('layouts.admin')

@section('title', 'Knowledge Base Articles')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Knowledge Base Articles</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>กลับหน้าหลัก
            </a>
            <a href="{{ route('admin.tickets.kb-articles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>Create Article
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Help Articles</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Views</th>
                            <th>Helpful</th>
                            <th>Visibility</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articles as $article)
                        <tr>
                            <td>
                                <strong>{{ $article->title }}</strong>
                                @if($article->tags)
                                    <br>
                                    @foreach($article->tags as $tag)
                                        <span class="badge badge-info badge-sm">{{ $tag }}</span>
                                    @endforeach
                                @endif
                            </td>
                            <td>{{ $article->category->name ?? 'Uncategorized' }}</td>
                            <td>{{ $article->author->name ?? 'N/A' }}</td>
                            <td>{{ $article->view_count }}</td>
                            <td>
                                @if($article->helpful_count > 0)
                                    <span class="text-success">
                                        <i class="fas fa-thumbs-up"></i> {{ $article->helpful_count }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('admin.tickets.kb-articles.toggle', $article->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-{{ $article->is_public ? 'success' : 'warning' }}">
                                        {{ $article->is_public ? 'Public' : 'Private' }}
                                    </button>
                                </form>
                            </td>
                            <td>{{ $article->updated_at->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('admin.tickets.kb-articles.edit', $article->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.tickets.kb-articles.destroy', $article->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No articles yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $articles->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
