@extends('layouts.app')

@section('css')
    @vite('resources/css/user/attendance-register.css')
@endsection

@section('content')
    <div class="attendance__content">
        <h1 class="attendance__heading">勤怠登録</h1>

        {{-- 現在のステータス --}}
        <div class="attendance__status">
            <p class="attendance__status--item">{{ $status }}</p>
        </div>

        {{-- 現在の日付 --}}
        <div class="current-date">
            <input class="current-date__item" type="text" value="{{ $formattedDate }}" readonly>
        </div>

        {{-- 現在の時刻 --}}
        <div class="current-time">
            <input class="current-time__item" type="text" id="currentTime" value="{{ $formattedTime }}" readonly>
        </div>

        {{-- 退勤完了時のメッセージ --}}
        @if (session('message'))
            <div class="alert-message">
                {{ session('message') }}
            </div>
        @endif

        {{-- 打刻ボタン --}}
        <div class="attendance__button">

            {{-- 勤務外：出勤 --}}
            @if ($status === '勤務外')
                <form method="POST" action="{{ route('attendance.clockIn') }}">
                    @csrf

                    <button class="attendance__button--submit--clock-in" type="submit">
                        出勤
                    </button>
                </form>

                {{-- 出勤中：退勤・休憩入 --}}
            @elseif ($status === '出勤中')
                <form method="POST" action="{{ route('attendance.clockOut') }}">
                    @csrf

                    <button class="attendance__button--submit--clock-out" type="submit">
                        退勤
                    </button>
                </form>

                <form method="POST" action="{{ route('attendance.breakIn') }}">
                    @csrf

                    <button class="attendance__button--submit--break-in" type="submit">
                        休憩入
                    </button>
                </form>

                {{-- 休憩中：休憩戻 --}}
            @elseif ($status === '休憩中')
                <form method="POST" action="{{ route('attendance.breakOut') }}">
                    @csrf

                    <button class="attendance__button--submit--break-out" type="submit">
                        休憩戻
                    </button>
                </form>

                {{-- 退勤済 --}}
            @elseif ($status === '退勤済')
                <p class="attendance__message">
                    お疲れ様でした。
                </p>
            @endif

        </div>
    </div>

    {{-- 表示時刻を1秒ごとに更新 --}}
    <script>
        function updateTime() {
            const now = new Date();
            const options = {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            };

            document.getElementById('currentTime').value =
                now.toLocaleTimeString('ja-JP', options);
        }

        setInterval(updateTime, 1000);
        updateTime();
    </script>
@endsection