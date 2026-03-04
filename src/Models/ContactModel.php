<?php

declare(strict_types=1);

namespace App\Models;

use HeartPhrame\Database\Model;

class ContactModel extends Model
{
    protected string $table = 'contacts';

    protected array $fillable = ['name', 'email', 'subject', 'message'];

    protected bool $timestamps = false;

    protected string $primaryKey = 'id';

    public function name(?string $name = null): string
    {
        if (is_string($name)) {
            return $this->attributes['name'] = $name;
        }

        if (!is_string($name = $this->attributes['name'] ?? null)) {
            throw new \RuntimeException('Name is not set.');
        }

        return $name;
    }

    public function email(?string $email = null): string
    {
        if (is_string($email)) {
            return $this->attributes['email'] = $email;
        }

        if (!is_string($email = $this->attributes['email'] ?? null)) {
            throw new \RuntimeException('Email is not set.');
        }

        return $email;
    }

    public function subject(?string $subject = null): string
    {
        if (is_string($subject)) {
            return $this->attributes['subject'] = $subject;
        }

        if (!is_string($subject = $this->attributes['subject'] ?? null)) {
            throw new \RuntimeException('Subject is not set.');
        }

        return $subject;
    }

    public function message(?string $message = null): string
    {
        if (is_string($message)) {
            return $this->attributes['message'] = $message;
        }

        if (!is_string($message = $this->attributes['message'] ?? null)) {
            throw new \RuntimeException('Message is not set.');
        }

        return $message;
    }

    protected function validate(): void
    {
        $this->name();
        $this->email();
        $this->subject();
        $this->message();
    }
}
