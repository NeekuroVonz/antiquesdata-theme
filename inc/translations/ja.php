<?php
/**
 * Japanese UI translations (msgid => translation).
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

return array(
	// Header / nav / footer
	'Account'                         => 'アカウント',
	'Subscribe'                       => '購読する',
	'Log out'                         => 'ログアウト',
	'Log in'                          => 'ログイン',
	'Register'                        => '新規登録',
	'Home'                            => 'ホーム',
	'English'                         => 'English',
	'Japanese'                        => '日本語',
	'Language'                        => '言語',
	'Terms of Service'                => '利用規約',
	'Privacy Policy'                  => 'プライバシーポリシー',
	'Commerce disclosure'             => '特定商取引法に基づく表記',
	'Legal links'                     => '法的情報',

	// Homepage hero & filters
	'Bid, buy, and discover rare antiques' => '希少な骨董品を入札・購入・発見',
	'A marketplace-style homepage inspired by eBay. Publish listings as posts and start selling.' => 'eBay風のマーケットプレイス。出品して販売を始めましょう。',
	'Filter listings'                 => '出品を絞り込む',
	'Refine your search'              => '検索条件を絞り込む',
	'Search'                          => '検索',
	'Auction'                         => 'オークション',
	'Price'                           => '価格',
	'Display'                         => '表示',
	'Keywords'                        => 'キーワード',
	'Search titles…'                  => 'タイトルで検索…',
	'Listing status'                  => '出品ステータス',
	'All listings'                    => 'すべて',
	'Active auctions'                 => '開催中',
	'Ended auctions'                  => '終了済み',
	'Ends within'                     => '終了まで',
	'Any time'                        => '指定なし',
	'24 hours'                        => '24時間以内',
	'3 days'                          => '3日以内',
	'7 days'                          => '7日以内',
	'Category'                        => 'カテゴリー',
	'All categories'                  => 'すべてのカテゴリー',
	'Min price'                       => '最低価格',
	'Max price'                       => '最高価格',
	'Only with price'                 => '価格ありのみ',
	'Sort by'                         => '並び替え',
	'Newest'                          => '新着順',
	'Ending soon'                     => '終了間近',
	'Price: Low to High'              => '価格：安い順',
	'Price: High to Low'              => '価格：高い順',
	'Per page'                        => '表示件数',
	'Apply filters'                   => 'フィルターを適用',
	'Clear filters'                   => 'フィルターをクリア',
	'Featured listings'               => '注目の出品',
	'Showing %1$d-%2$d of %3$d products' => '%3$d件中 %1$d–%2$d件を表示',
	'CURRENT BID'                     => '現在の入札',
	'Bid not available'               => '入札情報なし',
	'No external products found.'     => '商品が見つかりませんでした。',
	'Database error: %s'              => 'データベースエラー: %s',
	'Connected to table: %s'          => '接続テーブル: %s',
	'Product pagination'              => 'ページ送り',
	'Prev'                            => '前へ',
	'Next'                            => '次へ',

	// Product detail
	'Product not found'               => '商品が見つかりません',
	'This product is unavailable.'  => 'この商品は現在ご利用いただけません。',
	'Back to listings'                => '出品一覧に戻る',
	'Preview image %d'                => '画像 %d をプレビュー',
	'View source listing'             => '元の出品を見る',
	'Details'                         => '詳細',
	'Details overview'              => '詳細概要',
	'Description'                     => '説明',

	// Time left
	'Time not available'              => '時間情報なし',
	'Ended'                           => '終了',
	'%1$d day %2$d hr left'           => '残り %1$d日 %2$d時間',
	'%1$d days %2$d hrs left'         => '残り %1$d日 %2$d時間',
	'%1$d hrs %2$d mins left'         => '残り %1$d時間 %2$d分',
	'%d mins left'                    => '残り %d分',

	// Auth — login
	'Username or email'               => 'ユーザー名またはメール',
	'Password'                        => 'パスワード',
	'Remember me'                     => 'ログイン状態を保持',
	'Lost your password?'             => 'パスワードをお忘れですか？',
	'Create an account'               => 'アカウントを作成',
	'Invalid username or password.'   => 'ユーザー名またはパスワードが正しくありません。',
	'Please enter your username and password.' => 'ユーザー名とパスワードを入力してください。',
	'Could not log you in.'           => 'ログインできませんでした。',
	'Invalid login request.'          => 'ログインリクエストが無効です。',

	// Auth — register
	'Create account'                  => 'アカウント作成',
	'Registration is required. After signing up, complete a one-time payment to unlock all listings.' => '登録が必要です。登録後、一回限りのお支払いですべての出品を閲覧できます。',
	'Please fill in all fields.'      => 'すべての項目を入力してください。',
	'Enter a valid email address.'    => '有効なメールアドレスを入力してください。',
	'Passwords do not match.'       => 'パスワードが一致しません。',
	'Use at least 8 characters for your password.' => 'パスワードは8文字以上にしてください。',
	'Could not create your account. The username or email may already be in use.' => 'アカウントを作成できませんでした。ユーザー名またはメールが既に使用されている可能性があります。',
	'Username'                        => 'ユーザー名',
	'Email'                           => 'メールアドレス',
	'Confirm password'                => 'パスワード（確認）',
	'Already have an account? Log in' => 'アカウントをお持ちですか？ログイン',
	'Invalid registration request.'   => '登録リクエストが無効です。',

	// Subscribe / Stripe
	'Membership payment is bypassed in this environment (see .env).' => 'この環境では会員料金がバイパスされています（.env を参照）。',
	'Continue to site'                => 'サイトに進む',
	'Log in or create an account to complete payment and unlock all listings.' => 'ログインまたは登録後、お支払いですべての出品を閲覧できます。',
	'Your subscription is active. You have full access.' => '購読が有効です。すべてのコンテンツにアクセスできます。',
	'Thank you! Your payment was successful.' => 'ありがとうございます。お支払いが完了しました。',
	'Unlock every listing on the site with a one-time payment of %s.' => '一回限りのお支払い %s でサイト内のすべての出品を閲覧できます。',
	'Checkout was cancelled.'         => '決済がキャンセルされました。',
	'Checkout session expired. Please try again.' => '決済セッションの有効期限が切れました。もう一度お試しください。',
	'Stripe is not configured. Add STRIPE_SECRET_KEY to .env.' => 'Stripe が設定されていません。.env に STRIPE_SECRET_KEY を追加してください。',
	'Payment could not be started. Check Stripe keys in .env.' => '決済を開始できませんでした。.env の Stripe キーを確認してください。',
	'Stripe secret key is missing. Add STRIPE_SECRET_KEY to your .env file.' => 'Stripe シークレットキーがありません。.env に STRIPE_SECRET_KEY を追加してください。',
	'Pay %s with Stripe'              => 'Stripeで %s を支払う',
	'Full site access'                => 'サイト全体へのアクセス',
	'Browse listings'                 => '出品を見る',

	// Membership / system
	'Invalid logout request.'           => 'ログアウトリクエストが無効です。',
	'Invalid product ID.'             => '商品IDが無効です。',
	'No product table found.'         => '商品テーブルが見つかりません。',
	'No product ID column found.'     => '商品ID列が見つかりません。',
	'No product title column found.'  => '商品タイトル列が見つかりません。',
	'Product not found.'              => '商品が見つかりません。',
	'No product tables found in external database.' => '外部データベースに商品テーブルが見つかりません。',
	'Could not read product table columns.' => '商品テーブルの列を読み取れませんでした。',
	'No title-like column found in external products table.' => '商品テーブルにタイトル列が見つかりません。',

	// Widgets / misc
	'Primary Menu'                    => 'メインメニュー',
	'Marketplace Filters'             => 'マーケットプレイスフィルター',
	'Add filter widgets for the left sidebar.' => '左サイドバー用のフィルターウィジェットを追加します。',
	'Set _listing_price'              => '_listing_price を設定',
	'Buy It Now'                      => '即決購入',

	// Legal page labels (shared)
	'Last updated: April 18, 2026'    => '最終更新日: 2026年4月18日',
	'Introduction'                    => 'はじめに',
	'Other policies'                  => 'その他のポリシー',
	'Contact us'                      => 'お問い合わせ',
	'Contact'                         => 'お問い合わせ',
	'Legal Name:'                     => '氏名:',
	'Company Name:'                   => '会社名:',
	'Head of Operations:'             => '運営責任者:',
	'Company Number (法人番号):'      => '法人番号:',
	'Address:'                        => '住所:',
	'Phone:'                          => '電話:',
	'Email:'                          => 'メール:',
	'No image'                        => '画像なし',
	'Log In'                          => 'ログイン',
);
