<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $share->document->title }} - Shared Document</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center px-4 py-6 sm:px-6 lg:px-8">
        <div class="w-full max-w-2xl">
            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-4xl font-bold text-white mb-2">Shared Document</h1>
                <p class="text-indigo-100">A document has been shared with you</p>
            </div>

            <!-- Document Card -->
            <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
                <!-- Document Info -->
                <div class="px-6 py-8 sm:px-10">
                    <!-- File Icon & Title -->
                    <div class="flex items-start gap-4 mb-6">
                        <div class="shrink-0">
                            <div class="flex items-center justify-center h-12 w-12 rounded-lg bg-indigo-100">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl font-bold text-gray-900 wrap-break-word">{{ $share->document->title }}</h2>
                            @if ($share->document->description)
                                <p class="mt-2 text-sm text-gray-600">{{ $share->document->description }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Document Details -->
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 mb-8">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">File Name</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-all">{{ $share->document->original_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">File Size</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($share->document->file_size / 1024 / 1024, 2) }} MB</dd>
                        </div>
                        @if ($share->max_downloads)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Downloads</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $share->download_count }} / {{ $share->max_downloads }}</dd>
                            </div>
                        @endif
                        @if ($share->expires_at)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Expires At</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $share->expires_at->format('M d, Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($share->access_notes)
                        <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-800"><strong>Notes:</strong> {{ $share->access_notes }}</p>
                        </div>
                    @endif
                </div>

                <!-- Password Verification Form -->
                @if ($share->requiresPassword() && !session()->get("share.{$share->share_token}.verified"))
                    <div class="border-t border-gray-200 px-6 py-8 sm:px-10 bg-gray-50">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Enter Password</h3>
                        <form method="POST" action="{{ route('share.verify-password', $share->share_token) }}">
                            @csrf
                            <input
                                type="text"
                                name="username"
                                value="{{ $share->createdBy->email }}"
                                autocomplete="username"
                                tabindex="-1"
                                aria-hidden="true"
                                class="sr-only"
                            />

                            <div class="mb-4">
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <input type="password" name="password" id="password" required autocomplete="current-password"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Enter the password"
                                />
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition">
                                Verify Password
                            </button>
                        </form>
                    </div>
                @else
                    <!-- Download Button -->
                    <div class="border-t border-gray-200 px-6 py-8 sm:px-10 bg-gray-50">
                        <a href="{{ route('share.download', $share->share_token) }}" 
                           class="inline-flex items-center justify-center w-full px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Download File
                        </a>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-indigo-100 text-sm">
                <p>Shared by {{ $share->createdBy->name }} • {{ $share->created_at->diffForHumans() }}</p>
            </div>
        </div>
    </div>
</body>
</html>
