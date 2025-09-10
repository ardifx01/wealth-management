# 📊 User Wallet Dashboard

![Preview](./.github/assets/thumb.png)

A Laravel + Filament-based transaction and wallet management system. Provides real-time balance monitoring by day, month, year-to-date (YTD), and all-time, complete with multi-wallet and income/expense tracking.

> *"If you don't find a way to make money while you sleep, you will work until you die."*
> — Warren Buffett

---

## 🚀 Key Features

* 📈 Real-time balance statistics (daily, weekly, monthly, YTD, all-time)
* 🧾 Transaction logging (income, expense, and inter-wallet transfers)
* 👛 Multiple wallets per user
* 🔐 User authentication with Laravel

---

## 🛠️ Installation

```bash
git clone https://github.com/ardifx01/wealth-management.git
cd wealth-management

cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
