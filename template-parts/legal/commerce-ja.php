<?php
/**
 * Commerce disclosure — Japanese (特定商取引法に基づく表記).
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}
?>
<h1 class="legal-page-title">特定商取引法に基づく表記</h1>
<p class="legal-muted">最終更新日: 2026年4月18日</p>

<p class="legal-lead">本ページは、オンライン決済の前後にお客様に表示する事業者情報です。販売事業者の明示、料金発生のタイミング、返金の考え方等を記載しています。<a href="https://support.stripe.com/questions/how-to-create-and-display-a-commerce-disclosure-page" target="_blank" rel="noopener noreferrer">Stripe のガイダンス</a>に沿った構成です。<a href="<?php echo esc_url(antiques_marketplace_page_link('terms-of-service')); ?>">利用規約</a>および<a href="<?php echo esc_url(antiques_marketplace_page_link('privacy-policy')); ?>">プライバシーポリシー</a>とあわせてご確認ください。</p>

<h2>販売事業者</h2>
<ul>
	<li><strong>サイト名:</strong> <?php echo esc_html(get_bloginfo('name')); ?></li>
	<li><strong>氏名:</strong> Akira Washiya | 鷲谷彬</li>
	<li><strong>会社名:</strong> 有限会社鳥海メディカルサービス</li>
	<li><strong>運営責任者:</strong> Akira Washiya</li>
	<li><strong>法人番号:</strong> 2060002006021</li>
	<li><strong>住所:</strong> 栃木県宇都宮市一条３丁目２番２９号レオパレス２１－１０２号室</li>
	<li><strong>電話:</strong> <a href="tel:08012292520">080-1229-2520</a></li>
	<li><strong>事業内容:</strong> オンライン骨董品マーケットプレイス（出品・入札等の情報をカタログデータから表示）</li>
	<li><strong>ウェブサイト:</strong> <?php echo esc_html(home_url('/')); ?></li>
	<li><strong>メール:</strong> <a href="mailto:<?php echo esc_attr(get_bloginfo('admin_email')); ?>"><?php echo esc_html(get_bloginfo('admin_email')); ?></a></li>
</ul>
<p>税務・決済登録に使用する<strong>法人名</strong>および<strong>住所</strong>は、決済代行業者および公的记录と一致させてください。</p>

<h2>販売内容</h2>
<p>機能により、チェックアウト、請求書、または出品説明に記載の<strong>サービスへのアクセス</strong>または<strong>商品</strong>を購入いただく場合があります。各取引の範囲と価格は、チェックアウト画面、請求書、または書面契約に記載されます。</p>

<h2>価格および追加料金</h2>
<p>価格はチェックアウトまたは請求書に表示される通貨で表示されます。合計額は料金に、法令で徴収が必要な税金を加えた金額です。サブスクリプション等を事前に同意いただかない限り、継続課金は行いません。</p>

<h2>支払方法および時期</h2>
<p>チェックアウトで利用可能なカード等の決済は、<strong>Stripe</strong>（またはチェックアウト時に表示する他の決済代行業者）により処理されます。決済確定により、表示金額について当社および決済代行業者が指定の支払方法に請求することを承認したものとみなします。</p>
<p>請求書払いの場合、別途書面で定めない限り、請求書記載の期日までにお支払いください。</p>

<h2>提供時期</h2>
<p>デジタルサービスおよび情報は、チェックアウト時の説明に従って提供されます。物理的商品がある場合は、出品または契約に従います。</p>

<h2>キャンセル・変更・返金</h2>
<p><strong>確定した購入に対して料金が発生</strong>し、<strong>返金は約束した内容および既に提供した内容</strong>に依存します。</p>
<ul>
	<li><strong>お支払い後:</strong> チェックアウト説明、請求書、または署名契約に記載の内容が対象です。未着手の作業について書面でキャンセルされた場合、法令および契約で許される範囲で未使用分を返金する場合があります。</li>
	<li><strong>作業開始後または成果物提供後:</strong> 完了または割り当て済みの作業に対する料金は、原則として<strong>返金不可</strong>です（別途書面合意または法令による場合を除く）。</li>
	<li><strong>請求に関するお問い合わせ:</strong> チャージバックの前に、可能な限り <a href="mailto:<?php echo esc_attr(get_bloginfo('admin_email')); ?>"><?php echo esc_html(get_bloginfo('admin_email')); ?></a> までご連絡ください。</li>
</ul>

<h2>プライバシー</h2>
<p><a href="<?php echo esc_url(antiques_marketplace_page_link('privacy-policy')); ?>">プライバシーポリシー</a>に個人データの取扱いを記載しています。カード情報は Stripe の規約およびセキュリティ慣行に従って処理されます。</p>

<h2>利用規約</h2>
<p>購入は<a href="<?php echo esc_url(antiques_marketplace_page_link('terms-of-service')); ?>">利用規約</a>（責任制限、準拠法を含む）にも従います。</p>

<h2>Stripe について</h2>
<p>Stripe は第三者の決済代行業者であり、第三者出品の販売者ではありません。<a href="https://stripe.com/legal/consumer" target="_blank" rel="noopener noreferrer">Stripe 利用者向け規約</a>をご確認ください。</p>
