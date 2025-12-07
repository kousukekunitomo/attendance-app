## 環境構築

### Dockerビルド
- `git clone https://github.com/kousukekunitomo/flea-market-app`
- `cd flea-market-app`
- `docker compose up -d --build`

---

### Laravel環境構築
- `docker compose exec app bash`
- `composer install`
- `cp .env.example .env`   # ← `.env` が存在しない場合はこれで作成
- `php artisan key:generate`
- `php artisan migrate`
- `php artisan db:seed`
- `php artisan storage:link`

---

## env

```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=secret

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Flea Market App"

STRIPE_KEY=公開キー
STRIPE_SECRET=シークレットキー
STRIPE_WEBHOOK_SECRET=whsec_xxxxx

```
## メール認証（Email Verification）

本アプリでは、ユーザー登録時にメール認証を行います。  
開発環境では MailHog を使ってメールを確認します。

1. ブラウザで `http://localhost:8000/register` から新規登録する  
2. 別タブで MailHog (`http://localhost:8025`) を開く  
3. 受信トレイに届いた「メールアドレス確認」メールを開く  
4. メール本文中の確認用リンクをクリックすると、アカウントが有効化されます

※ `.env` の `MAIL_HOST=mailhog`, `MAIL_PORT=1025` により、アプリから送信されたメールはすべて MailHog に届きます。

---

### ローカルアクセス
- 新規登録: http://localhost:8000/register  
- ログイン: http://localhost:8000/login  
- MailHog: http://localhost:8025  
- phpMyAdmin: http://localhost:8080  

---

## Stripe (dev)

1) Webhook リッスン  
```bash
stripe listen --forward-to http://localhost:8000/api/stripe/webhook
```

2) 出力された Signing secret を `.env` の  
```
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```  
に設定

3) 必要に応じて設定キャッシュクリア  
```bash
php artisan config:clear
```

---

## Demo Login
- Email: `admin@example.com`  
- Password: `mmmmmmmm`

---

## ER Diagram
<p align="center">
  <img src="public/images/er-diagram-flea-2025-10.png" alt="ER Diagram of flea-market-app" width="720">
</p>

---

# Running Feature Tests (SQLite)

テスト環境（`--env=testing`）では MySQL は不要。  
Laravel のテスト用 DB は **SQLite** を使用します。

## 1) One-time setup
```bash
mkdir -p database
[ -f database/testing.sqlite ] || : > database/testing.sqlite

cat > .env.testing <<'EOF'
APP_ENV=testing
APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=database/testing.sqlite
EOF
```

## 2) Run Feature tests
```bash
docker compose exec app bash -lc "
php artisan config:clear &&
php artisan migrate:fresh --env=testing &&
php artisan test --testsuite=Feature --env=testing
"
```

### php artisan migrate で「Base table or view already exists: orders」などのエラーが出る場合

すでに古いテーブル構成が残っている場合に発生します。  
開発環境を一度まっさらな状態にしてから、README の手順をやり直してください。

#### 1) Docker コンテナとボリュームを削除（DB を初期化）

```bash
docker compose down -v
docker compose up -d --build
