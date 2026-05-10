<?php

namespace App\Filament\Pages;

use App\Mail\ContactFormMail;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = '聯絡我們';

    protected static ?string $title = '聯絡我們';

    protected static ?string $navigationGroup = '設定';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.contact-page';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'name' => $user?->name ?? '',
            'email' => $user?->email ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('姓名')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('回覆信箱')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('subject')
                    ->label('主題')
                    ->options([
                        '帳號問題' => '帳號問題',
                        '訂閱問題' => '訂閱問題',
                        '功能建議' => '功能建議',
                        '其他' => '其他',
                    ])
                    ->required(),
                Textarea::make('message')
                    ->label('訊息內容')
                    ->required()
                    ->rows(5)
                    ->maxLength(2000),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            Mail::to('dindin1688888888@gmail.com')->send(new ContactFormMail(
                name: $data['name'],
                email: $data['email'],
                topic: $data['subject'],
                messageContent: $data['message'],
            ));
        } catch (\Throwable $e) {
            Log::error('ContactPage submit failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('訊息送出失敗')
                ->body('系統暫時無法寄送 Email，請稍後再試或直接寫信至 dindin1688888888@gmail.com。')
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('訊息已送出')
            ->body('感謝您的來信，我們會盡快回覆。')
            ->send();

        $this->form->fill([
            'name' => Auth::user()?->name ?? '',
            'email' => Auth::user()?->email ?? '',
        ]);
    }
}
