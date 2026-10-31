@extends('layouts.admin-app')

@section('css')
    @vite('resources/css/admin/admin-detail.css')
@endsection

@section('content')
    <div class="detail__content">
        <div class="detail__header">
            <h1 class="content__header--item">勤怠詳細</h1>
        </div>

        @if (session('status'))
            <p class="success-message">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div>
                <p>バリデーションエラーを検出しました。</p>

                @foreach ($errors->messages() as $key => $messages)
                    <p>エラーキー：{{ $key }}</p>

                    @foreach ($messages as $message)
                        <p>{{ $message }}</p>
                    @endforeach
                @endforeach
            </div>
        @endif
        <form class="form" action="{{ route('admin.attendance.update', $attendanceRecord['id']) }}" method="post">
            @csrf
            <div class="form__content">
                <div class="form__group">
                    <label class="form__header" for="name">名前</label>
                    <div class="form__input-group">
                        <input class="form__input form__input--name" id="name" type="text" name="name"
                            value="{{ $user->name }}" readonly>
                    </div>
                </div>
                <div class="form__group">
                    <label class="form__header">日付</label>
                    <div class="form__input-group">
                        {{-- 日付は表示のみ（修正不可）。名前欄と同様に枠線（border）を外す --}}
                        <input class="form__input form__input--date" type="text" value="{{ $attendanceRecord['year'] }}"
                            readonly>
                        <input class="form__input form__input--date" type="text" name="new_date"
                            value="{{ $attendanceRecord['date'] }}" readonly>
                    </div>
                </div>

                <div class="form__group">
                    <label class="form__header" for="new_clock_in">出勤・退勤</label>
                    <div class="form__input-group">
                        <input class="form__input" id="new_clock_in" type="text" name="new_clock_in"
                            value="{{ old('new_clock_in', $attendanceRecord['clock_in'] ? \Carbon\Carbon::parse($attendanceRecord['clock_in'])->format('H:i') : '') }}"
                            placeholder="HH:MM">
                        <p>〜</p>
                        <input class="form__input" type="text" name="new_clock_out"
                            value="{{ old('new_clock_out', $attendanceRecord['clock_out'] ? \Carbon\Carbon::parse($attendanceRecord['clock_out'])->format('H:i') : '') }}"
                            placeholder="HH:MM">
                    </div>
                </div>

                <div class="error-message">
                    <div></div>
                    <div class="error-message__item">
                        @error('new_clock_in')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                        @error('new_clock_out')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- 休憩は「休憩」「休憩1」「休憩2」…とセクションを分けて表示 --}}
                @php
                    $breaks = (isset($attendanceRecord['breaks']) && is_array($attendanceRecord['breaks']))
                        ? $attendanceRecord['breaks'] : [];
                @endphp
                @foreach($breaks as $index => $break)
                    <div class="form__group">
                        <label class="form__header">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</label>
                        <div class="form__input-group">
                            <input class="form__input" type="text" name="new_break_in[{{ $index }}]"
                                value="{{ old('new_break_in.' . $index, $break['break_in'] ? \Carbon\Carbon::parse($break['break_in'])->format('H:i') : '') }}"
                                placeholder="HH:MM">
                            <p>〜</p>
                            <input class="form__input" type="text" name="new_break_out[{{ $index }}]"
                                value="{{ old('new_break_out.' . $index, $break['break_out'] ? \Carbon\Carbon::parse($break['break_out'])->format('H:i') : '') }}"
                                placeholder="HH:MM">
                        </div>
                    </div>
                    <div class="error-message">
                        <div></div>
                        <div class="error-message__item">
                            @error('new_break_in.' . $index)
                            <p class="error-message">{{ $message }}</p>@enderror
                            @error('new_break_out.' . $index)
                            <p class="error-message">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endforeach
                {{-- 新しい休憩を追加できるよう末尾に空欄を1つ用意 --}}
                @php $newBreakIndex = count($breaks); @endphp
                <div class="form__group">
                    <label class="form__header">{{ $newBreakIndex === 0 ? '休憩' : '休憩' . ($newBreakIndex + 1) }}</label>
                    <div class="form__input-group">
                        <input class="form__input" type="text" name="new_break_in[{{ $newBreakIndex }}]"
                            value="{{ old('new_break_in.' . $newBreakIndex, '') }}" placeholder="HH:MM">
                        <p>〜</p>
                        <input class="form__input" type="text" name="new_break_out[{{ $newBreakIndex }}]"
                            value="{{ old('new_break_out.' . $newBreakIndex, '') }}" placeholder="HH:MM">
                    </div>
                </div>
                <div class="error-message">
                    <div></div>
                    <div class="error-message__item">
                        @error('new_break_in.' . $newBreakIndex)
                        <p class="error-message">{{ $message }}</p>@enderror
                        @error('new_break_out.' . $newBreakIndex)
                        <p class="error-message">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form__group">
                    <label class="form__header" for="comment">備考</label>
                    <div class="form__input-group">
                        <textarea class="form__textarea" name="comment"
                            id="comment">{{ $attendanceRecord['comment'] }}</textarea>
                    </div>
                </div>
                <div class="error-message">
                    <div></div>
                    <div class="error-message__item">
                        @error ('comment')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form__button">
                <button class="form__button--submit" type="submit">修正</button>
            </div>
        </form>
    </div>
@endsection