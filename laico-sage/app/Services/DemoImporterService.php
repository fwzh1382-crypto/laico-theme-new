<?php

declare(strict_types=1);

namespace App\Services;

use App\PostTypes\ProjectPostType;
use App\Support\ViteAssets;

/**
 * 1-Click Idempotent Demo Content & Main Pages Importer for Laico Sage.
 *
 * Creates (without duplicating if already present):
 * - The 6 primary WordPress Pages (`home`, `services`, `projects`, `journal`, `about`, `contact`)
 *   with Featured Images imported into the WP Media Library so Dynamic Page Hero Images work immediately!
 * - Inner collection/utility Pages (`kitchen`, `living`, `bath`, `cafe`, `studio`, `systems`, `process`,
 *   `materials`, `showroom`, `privacy`, `cookies`).
 * - WordPress Reading Options (`show_on_front = page`, `page_on_front`, `page_for_posts`).
 * - Primary & Footer Navigation Menus (`primary_navigation`, `footer_navigation`).
 * - All 15 Portfolio Projects (`laico_project`) with taxonomies, specs, gallery URLs, videos & Featured Images.
 * - All 6 Journal Articles (`post`) with categories, reading time, bilingual copy & Featured Images.
 */
final class DemoImporterService
{
    /**
     * @return array{ok: bool, pages: int, projects: int, posts: int, message: string}
     */
    public function importAll(): array
    {
        if (! current_user_can('manage_options')) {
            return ['ok' => false, 'pages' => 0, 'projects' => 0, 'posts' => 0, 'message' => 'دسترسی غیرمجاز.'];
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $pagesCreated = $this->seedPagesAndReadingSettings();
        $projectsCreated = $this->seedProjects();
        $postsCreated = $this->seedJournalPosts();
        $this->seedNavigationMenus();

        flush_rewrite_rules(false);

        return [
            'ok' => true,
            'pages' => $pagesCreated,
            'projects' => $projectsCreated,
            'posts' => $postsCreated,
            'message' => sprintf(
                'راه‌اندازی محتوای اولیه با موفقیت انجام شد (%d برگه، %d پروژه و %d مقالهٔ مجله بررسی/ایجاد شد).',
                $pagesCreated,
                $projectsCreated,
                $postsCreated
            ),
        ];
    }

    private function seedPagesAndReadingSettings(): int
    {
        $pages = [
            // 6 Main Pages (Hero loaded from Featured Image!)
            ['slug' => 'home', 'title' => 'صفحه اصلی', 'template' => '', 'hero' => 'images/hero-main.webp'],
            ['slug' => 'services', 'title' => 'خدمات', 'template' => 'template-services.blade.php', 'hero' => 'images/hero-services.webp'],
            ['slug' => 'projects', 'title' => 'پروژه‌ها', 'template' => 'template-projects.blade.php', 'hero' => 'images/hero-projects.webp'],
            ['slug' => 'journal', 'title' => 'مجله', 'template' => '', 'hero' => 'images/hero-journal.webp'],
            ['slug' => 'about', 'title' => 'درباره ما', 'template' => 'template-about.blade.php', 'hero' => 'images/hero-about.webp'],
            ['slug' => 'contact', 'title' => 'تماس با ما', 'template' => 'template-contact.blade.php', 'hero' => 'images/hero-contact.webp'],
            // Inner Collection & Info Pages
            ['slug' => 'kitchen', 'title' => 'آشپزخانه', 'template' => 'template-collection.blade.php', 'hero' => 'images/hero.webp'],
            ['slug' => 'living', 'title' => 'پذیرایی و اتاق خواب', 'template' => 'template-collection.blade.php', 'hero' => 'images/col-living.webp'],
            ['slug' => 'bath', 'title' => 'سرویس و حمام', 'template' => 'template-collection.blade.php', 'hero' => 'images/col-bath.webp'],
            ['slug' => 'cafe', 'title' => 'کافه و رستوران', 'template' => 'template-collection.blade.php', 'hero' => 'images/col-cafe.webp'],
            ['slug' => 'studio', 'title' => 'استودیو', 'template' => 'template-collection.blade.php', 'hero' => 'images/about-team.webp'],
            ['slug' => 'systems', 'title' => 'سیستم‌ها', 'template' => 'template-collection.blade.php', 'hero' => 'images/col-kitchen.webp'],
            ['slug' => 'process', 'title' => 'فرآیند طراحی و ساخت', 'template' => 'template-collection.blade.php', 'hero' => 'images/jr-plan.webp'],
            ['slug' => 'materials', 'title' => 'متریال و جزئیات', 'template' => 'template-collection.blade.php', 'hero' => 'images/detail.webp'],
            ['slug' => 'showroom', 'title' => 'شو روم', 'template' => 'template-showroom.blade.php', 'hero' => 'images/pj-pars.webp'],
            ['slug' => 'privacy', 'title' => 'حریم خصوصی', 'template' => 'template-legal.blade.php', 'hero' => ''],
            ['slug' => 'cookies', 'title' => 'کوکی‌ها', 'template' => 'template-legal.blade.php', 'hero' => ''],
        ];

        $count = 0;
        $idsBySlug = [];

        foreach ($pages as $p) {
            $existing = get_page_by_path($p['slug'], OBJECT, 'page');
            if ($existing instanceof \WP_Post) {
                $pageId = (int) $existing->ID;
            } else {
                $inserted = wp_insert_post([
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'post_name' => $p['slug'],
                    'post_title' => $p['title'],
                    'post_content' => '',
                ]);
                if (is_wp_error($inserted) || ! $inserted) {
                    continue;
                }
                $pageId = (int) $inserted;
                $count++;
            }

            if ($p['template'] !== '') {
                update_post_meta($pageId, '_wp_page_template', $p['template']);
            }

            if ($p['hero'] !== '' && ! has_post_thumbnail($pageId)) {
                $attId = $this->ensureThemeAssetAttachment($p['hero'], $p['title']);
                if ($attId > 0) {
                    set_post_thumbnail($pageId, $attId);
                }
            }

            $idsBySlug[$p['slug']] = $pageId;
        }

        if (! empty($idsBySlug['home']) && ! empty($idsBySlug['journal'])) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $idsBySlug['home']);
            update_option('page_for_posts', $idsBySlug['journal']);
        }

        return $count;
    }

    private function seedProjects(): int
    {
        $videoRes = ViteAssets::assetUrl('video/tour-residential.mp4');
        $videoCom = ViteAssets::assetUrl('video/tour-commercial.mp4');

        $projects = [
            [
                'slug' => 'firouzkuh', 'cat' => 'residential', 'space' => 'architecture', 'city' => 'firouzkuh',
                'year' => '1404', 'area' => 380, 'featured_slot' => 1,
                'title' => ['پروژه کوهستانی فیروزکوه', 'Firouzkuh Mountain Residence'],
                'client' => ['کارفرمای خصوصی', 'Private Client'],
                'duration' => ['۱۲ ماه', '12 months'],
                'place' => ['فیروزکوه، ارتفاعات البرز', 'Firouzkuh, Alborz Highlands'],
                'lede' => ['معماری و طراحی نما در دل طبیعت کوهستانی با حجم‌های آرام و متریال مقاوم در اقلیم سرد.', 'Mountain facade and interior architecture crafted for cold-climate resilience and calm views.'],
                'text' => [
                    ['پروژه کوهستانی فیروزکوه بر پایهٔ هم‌نشینی سنگ بومی، چوب فرآوری‌شده و قاب‌های بزرگ شیشه‌ای رو به دره طراحی شد.', 'The Firouzkuh residence pairs local stone, thermally treated timber and wide glazed frames facing the valley.'],
                    ['جزئیات آب‌بندی نما و عایق‌بندی حرارتی به‌گونه‌ای اجرا شد که آرامش فضای داخلی در تمام فصول حفظ شود.', 'Envelope detailing and thermal insulation keep the interior serene through harsh mountain winters.'],
                ],
                'cover' => 'images/firouzkuh.webp',
                'gallery' => ['images/firouzkuh.webp', 'images/pj-lavasan.webp', 'images/detail.webp', 'images/jr-lighting.webp'],
                'video' => $videoRes,
            ],
            [
                'slug' => 'cafe-amol', 'cat' => 'commercial', 'space' => 'hall', 'city' => 'amol',
                'year' => '1404', 'area' => 210, 'featured_slot' => 2,
                'title' => ['طراحی کافه آمل', 'Amol Specialty Café'],
                'client' => ['مجموعه کافه آمل', 'Amol Café Group'],
                'duration' => ['۵ ماه', '5 months'],
                'place' => ['آمل، مازندران', 'Amol, Mazandaran'],
                'lede' => ['طراحی داخلی و اجرای فضای تجاری و کافه با سیرکولاسیون روان باریستا و نورپردازی گرم.', 'Commercial café interior with fluid barista workflow, acoustic timber and warm lighting.'],
                'text' => [
                    ['در کافه آمل، کانتر اصلی به‌عنوان قلب عملکردی فضا طراحی شد تا سرویس‌دهی سریع بدون تداخل با مسیر نشستن مهمانان انجام شود.', 'In Amol Café, the central bar anchors workflow so rapid service never crosses guest seating paths.'],
                    ['ترکیب چوب طبیعی، رنگ سرمه‌ای امضای لایکو و کنترل آکوستیک سقف، فضایی صمیمی برای گفت‌وگو ساخته است.', 'Natural timber, signature Laico navy surfaces and ceiling acoustics create an intimate setting for conversation.'],
                ],
                'cover' => 'images/cafe-amol.webp',
                'gallery' => ['images/cafe-amol.webp', 'images/col-cafe.webp', 'images/proj-acajou.webp', 'images/detail.webp'],
                'video' => $videoCom,
                'sketch_set' => 'cafe',
            ],
            [
                'slug' => 'gym', 'cat' => 'commercial', 'space' => 'sports', 'city' => 'amol',
                'year' => '1404', 'area' => 450, 'featured_slot' => 3,
                'title' => ['طراحی باشگاه', 'Boutique Athletic Club'],
                'client' => ['مجموعه ورزشی مدرن', 'Modern Athletic Group'],
                'duration' => ['۶ ماه', '6 months'],
                'place' => ['آمل، مازندران', 'Amol, Mazandaran'],
                'lede' => ['فضای ورزشی مدرن با نورپردازی خطی ضدخیرگی، تهویهٔ پنهان و زون‌بندی دقیق تمرینی.', 'Modern athletic club featuring glare-free linear lighting, concealed HVAC and clear training zones.'],
                'text' => [
                    ['طراحی باشگاه با هدف فاصله گرفتن از فضاهای شلوغ مرسوم و خلق یک محیط تمرینی متمرکز و باکیفیت شکل گرفت.', 'This athletic space moves away from visual clutter toward a focused, architectural training environment.'],
                    ['کف‌پوش‌های جاذب ضربه، آینه‌های قدی بدون قاب و نور خطی سقفی، انرژی فضا را بدون خستگی چشم حفظ می‌کنند.', 'Shock-absorbing floors, frameless full-height mirrors and linear ceiling slots sustain energy without visual fatigue.'],
                ],
                'cover' => 'images/gym.webp',
                'gallery' => ['images/gym.webp', 'images/services-sport.webp', 'images/pj-noor.webp', 'images/jr-lighting.webp'],
                'video' => $videoCom,
            ],
            [
                'slug' => 'kindergarten', 'cat' => 'commercial', 'space' => 'educational', 'city' => 'amol',
                'year' => '1403', 'area' => 340, 'featured_slot' => 4,
                'title' => ['طراحی مهد کودک نقش دوزک', 'Naghsh-e Doozak Kindergarten'],
                'client' => ['مجموعه آموزشی نقش دوزک', 'Naghsh-e Doozak Center'],
                'duration' => ['۷ ماه', '7 months'],
                'place' => ['آمل، مازندران', 'Amol, Mazandaran'],
                'lede' => ['فضای آموزشی و کودک محور با گوشه‌های نرم، چوب طبیعی، نور روز فراوان و مقیاس انسانی کودک.', 'Child-centred educational architecture with rounded joinery, natural timber and abundant daylight.'],
                'text' => [
                    ['در مهد کودک نقش دوزک، تمام ارتفاع قفسه‌ها، دستگیره‌ها و پنجره‌ها بر اساس ارگونومی و دید کودک طراحی و در کارگاه لایکو ساخته شد.', 'Every shelf height, handle and window sill was scaled to children’s ergonomics and custom-built in the Laico workshop.'],
                    ['استفاده از پوشش‌های غیرسمی و لبه‌های گردشده، امنیت و آزادی حرکت کودکان را هم‌زمان تضمین می‌کند.', 'Non-toxic finishes and radiused timber edges ensure safety while encouraging free movement and play.'],
                ],
                'cover' => 'images/kindergarten.webp',
                'gallery' => ['images/kindergarten.webp', 'images/jr-plan.webp', 'images/col-living.webp', 'images/detail.webp'],
                'video' => $videoCom,
            ],
            [
                'slug' => 'aftab', 'cat' => 'residential', 'space' => 'kitchen', 'city' => 'tehran',
                'year' => '1403', 'area' => 310, 'featured_slot' => 0,
                'title' => ['پنت‌هاوس آفتاب', 'Penthouse Aftab'],
                'client' => ['خانوادهٔ کریمی', 'Karimi family'],
                'duration' => ['۸ ماه', '8 months'],
                'place' => ['تهران، سعادت‌آباد', 'Saadat Abad, Tehran'],
                'lede' => ['جزیرهٔ سنگی، حجم سرمه‌ای، و دو صندلی که مسیر آشپزخانه را تعریف می‌کنند.', 'A stone island, a navy volume, and two stools that set the path of the kitchen.'],
                'text' => [
                    ['پنت‌هاوس آفتاب برای یک خانوادهٔ چهارنفره طراحی شد. جزیره هم میز کار است و هم میز صبحانه. صفحهٔ سنگ از کف جزیره بالا می‌آید و درز افقی حذف شده.', 'Penthouse Aftab was planned for a family of four. The island is both worktop and breakfast table. Stone rises from the floor of the island; the horizontal joint is gone.'],
                    ['قفسه‌های باز فقط برای چیزهایی‌اند که هر روز استفاده می‌شوند. بقیه پشت درِ بدون دستگیره است.', 'Open shelves hold only what is used every day. Everything else sits behind a handleless door.'],
                ],
                'cover' => 'images/proj-aftab.webp',
                'gallery' => ['images/hero.webp', 'images/col-kitchen.webp', 'images/detail.webp', 'images/pj-baran.webp'],
                'video' => $videoRes,
            ],
            [
                'slug' => 'haraz', 'cat' => 'residential', 'space' => 'living', 'city' => 'mazandaran',
                'year' => '1404', 'area' => 420, 'featured_slot' => 0,
                'title' => ['ویلای هراز', 'Villa Haraz'],
                'client' => ['آقای رضایی', 'Mr. Rezaei'],
                'duration' => ['۱۱ ماه', '11 months'],
                'place' => ['جادهٔ هراز، مازندران', 'Haraz Road, Mazandaran'],
                'lede' => ['دیوار سنگ، کف چوب، و یک مبل. اتاق بیشتر از این لازم نداشت.', 'A stone wall, a timber floor, one sofa. The room did not need more.'],
                'text' => [
                    ['ویلای هراز بازسازی کامل نشیمن و اتاق‌ها بود. دیوار سنگی موجود ماند. نور از یک شکاف سقفی می‌آید، نه از لوستر.', 'Villa Haraz was a full remake of the living room and bedrooms. The existing stone wall stayed. Light comes from a ceiling slot, not a chandelier.'],
                    ['کمدها با سیستم لایکو ساخته شدند تا با آشپزخانهٔ خانه هم‌زبان باشند.', 'Wardrobes were built on the Laico system so they share a language with the kitchen.'],
                ],
                'cover' => 'images/proj-haraz.webp',
                'gallery' => ['images/pj-lavasan.webp', 'images/col-living.webp', 'images/jr-lighting.webp', 'images/pj-sabz.webp'],
                'video' => $videoRes,
            ],
            [
                'slug' => 'acajou', 'cat' => 'commercial', 'space' => 'hall', 'city' => 'amol',
                'year' => '1404', 'area' => 180, 'featured_slot' => 0,
                'title' => ['کافه اکاژو', 'Café Acajou'],
                'client' => ['مجموعهٔ کافه اکاژو', 'Acajou Café Group'],
                'duration' => ['۵ ماه', '5 months'],
                'place' => ['آمل، خیابان امام', 'Imam St., Amol'],
                'lede' => ['چوب، پیشخوان سرمه‌ای، و میزهایی که برای ماندن طراحی شده‌اند.', 'Timber, a navy counter, and tables designed for staying.'],
                'text' => [
                    ['اکاژو یک کافهٔ روزانه است، نه یک فضای نمایشی. پیشخوان برای کار باریستا عمق کافی دارد و مهمان مسیر جداگانه‌ای دارد.', 'Acajou is a daily café, not a showroom. The counter is deep enough for the barista, and the guest has a separate path.'],
                    ['دیوار چوب‌کوب صدا را می‌گیرد. آویزها پایین‌اند تا میز، نه سقف، روشن باشد.', 'The timber wall takes the sound. Pendants hang low so the table is lit, not the ceiling.'],
                ],
                'cover' => 'images/proj-acajou.webp',
                'gallery' => ['images/col-cafe.webp', 'images/pj-pars.webp', 'images/detail.webp', 'images/jr-lighting.webp'],
                'video' => $videoCom,
            ],
            [
                'slug' => 'darya', 'cat' => 'residential', 'space' => 'bath', 'city' => 'mazandaran',
                'year' => '1403', 'area' => 160, 'featured_slot' => 0,
                'title' => ['خانه دریا', 'House Darya'],
                'client' => ['خانوادهٔ نوری', 'Nouri family'],
                'duration' => ['۶ ماه', '6 months'],
                'place' => ['ساحل خزر، مازندران', 'Caspian coast, Mazandaran'],
                'lede' => ['یک وان سنگی، دو آینه، و برنجی که قرار نیست پنهان شود.', 'One stone tub, two mirrors, and brass that is not meant to hide.'],
                'text' => [
                    ['خانهٔ دریا حمام اصلی یک ویلای کوچک است. وان مرکز اتاق است. کابینت سرمه‌ای همان سیستم آشپزخانهٔ لایکو است.', 'House Darya is the main bath of a small villa. The tub is the centre of the room. The navy cabinet is the Laico kitchen system.'],
                    ['پنجره‌ای رو به حیاط نور را نرم می‌کند. هیچ نور نقطه‌ای تندی بالای آینه نیست.', 'A window to the court softens the light. There is no hard spotlight over the mirror.'],
                ],
                'cover' => 'images/proj-darya.webp',
                'gallery' => ['images/col-bath.webp', 'images/pj-mehr.webp', 'images/detail.webp', 'images/jr-door.webp'],
                'video' => $videoRes,
            ],
            [
                'slug' => 'complex', 'cat' => 'commercial', 'space' => 'office', 'city' => 'amol',
                'year' => '1404', 'area' => 2700, 'featured_slot' => 0,
                'title' => ['مجتمع تجاری آفتاب', 'Aftab Commercial Complex'],
                'client' => ['شرکت سرمایه‌گذاری آفتاب', 'Aftab Investment Co.'],
                'duration' => ['۱۸ ماه', '18 months'],
                'place' => ['آمل، بلوار شهید بهشتی', 'Beheshti Blvd., Amol'],
                'lede' => ['یک نمای پیوسته که شب‌ها به چراغ شهر تبدیل می‌شود.', "One continuous facade that turns into the city's lantern at night."],
                'text' => [
                    ['مجتمع آفتاب ترکیبی از خرده‌فروشی، رستوران و فضای تفریحی است. طراحی داخلی و نما با یک زبان واحد انجام شد.', 'Aftab combines retail, restaurants and leisure. Interior and facade were designed in one language.'],
                    ['مسیر پیاده بدون پله و روشنایی خطی، جریان مشتری را بدون تابلوی اضافه هدایت می‌کند.', 'A step-free route and linear lighting guide visitors without extra signage.'],
                ],
                'cover' => 'images/projects-card.webp',
                'gallery' => ['images/pj-pars.webp', 'images/pj-noor.webp', 'images/services-sport.webp', 'images/services-clinic.webp'],
                'video' => $videoCom,
            ],
            [
                'slug' => 'sabz', 'cat' => 'residential', 'space' => 'living', 'city' => 'tehran',
                'year' => '1404', 'area' => 135, 'featured_slot' => 0,
                'title' => ['آپارتمان سبز', 'Sabz Apartment'],
                'client' => ['خانم مرادی', 'Ms. Moradi'],
                'duration' => ['۴ ماه', '4 months'],
                'place' => ['تهران، ونک', 'Vanak, Tehran'],
                'lede' => ['یک کتابخانهٔ سرمه‌ای که دیوار نشیمن را به حافظهٔ خانه تبدیل می‌کند.', 'A navy bookcase that turns the living-room wall into the memory of the home.'],
                'text' => [
                    ['آپارتمان سبز با پلانی باز و یک دیوار کتابخانه‌ای بازطراحی شد. پنجره‌های بزرگ بالکن، نور روز را تا انتهای اتاق می‌برند.', 'Sabz was re-planned as one open room with a bookcase wall. Large balcony windows carry daylight to the back of the room.'],
                    ['کف چوب روشن و مبلمان کم‌حجم فضا را آرام نگه می‌دارند.', 'A pale timber floor and low-volume furniture keep the space calm.'],
                ],
                'cover' => 'images/pj-sabz.webp',
                'gallery' => ['images/pj-lavasan.webp', 'images/col-living.webp', 'images/jr-lighting.webp', 'images/jr-door.webp'],
                'video' => $videoRes,
            ],
            [
                'slug' => 'mehr', 'cat' => 'residential', 'space' => 'bath', 'city' => 'amol',
                'year' => '1404', 'area' => 38, 'featured_slot' => 0,
                'title' => ['حمام مهر', 'Mehr Bathroom'],
                'client' => ['آقای حیدری', 'Mr. Heidari'],
                'duration' => ['۷۰ روز', '70 days'],
                'place' => ['آمل، دریای ۱۲', 'Darya 12, Amol'],
                'lede' => ['سنگ از دیوار تا دوش؛ یک سطح، بدون درز اضافی.', 'Stone from wall to shower; one surface, no extra joints.'],
                'text' => [
                    ['حمام مهر یک فضای کوچک است که با سنگ یکپارچه بزرگ‌تر دیده می‌شود. آینهٔ پشت‌نورخورده نور نرم و بدون سایه می‌دهد.', 'Mehr is a small room that reads larger in continuous stone. A backlit mirror gives soft, shadow-free light.'],
                    ['کابینت معلق باعث می‌شود کف دیده شود و نظافت راحت‌تر باشد.', 'A floating vanity keeps the floor visible and cleaning easy.'],
                ],
                'cover' => 'images/pj-mehr.webp',
                'gallery' => ['images/col-bath.webp', 'images/proj-darya.webp', 'images/detail.webp', 'images/jr-door.webp'],
                'video' => $videoRes,
            ],
            [
                'slug' => 'baran', 'cat' => 'residential', 'space' => 'kitchen', 'city' => 'tehran',
                'year' => '1403', 'area' => 95, 'featured_slot' => 0,
                'title' => ['آشپزخانه باران', 'Baran Kitchen'],
                'client' => ['خانوادهٔ صالحی', 'Salehi family'],
                'duration' => ['۳ ماه', '3 months'],
                'place' => ['تهران، شهرک غرب', 'Shahrak-e Gharb, Tehran'],
                'lede' => ['آشپزخانه و ناهارخوری در یک حجم؛ آویزهای برنجی، جزیرهٔ سنگی.', 'Kitchen and dining as one volume; brass pendants, a stone island.'],
                'text' => [
                    ['در باران، آشپزخانه به ناهارخوری باز می‌شود. جزیره مرز نرمی میان آشپزی و نشستن ایجاد می‌کند.', 'At Baran the kitchen opens onto dining. The island is a soft border between cooking and sitting.'],
                    ['قفسه‌های باز بالای کانتر، ظرف‌های روزانه را در دسترس نگه می‌دارند.', 'Open shelves above the counter keep daily dishes within reach.'],
                ],
                'cover' => 'images/pj-baran.webp',
                'gallery' => ['images/hero.webp', 'images/col-kitchen.webp', 'images/proj-aftab.webp', 'images/detail.webp'],
                'video' => $videoRes,
            ],
            [
                'slug' => 'lavasan', 'cat' => 'residential', 'space' => 'living', 'city' => 'tehran',
                'year' => '1404', 'area' => 520, 'featured_slot' => 0,
                'title' => ['ویلای لواسان', 'Villa Lavasan'],
                'client' => ['آقای فرهمند', 'Mr. Farahmand'],
                'duration' => ['۱۴ ماه', '14 months'],
                'place' => ['لواسان، تهران', 'Lavasan, Tehran'],
                'lede' => ['یک سالن دو ارتفاعه که شب با نور گرم شومینه و شیشه به باغ باز می‌شود.', 'A double-height hall that opens to the garden through glass, warmed by the fireplace at night.'],
                'text' => [
                    ['ویلای لواسان حول یک سالن دو ارتفاعه طراحی شد. سقف چوبی و دیوار سرمه‌ای، قاب آرامی برای نمای باغ می‌سازند.', 'Villa Lavasan was planned around a double-height hall. A timber ceiling and a navy wall frame the garden quietly.'],
                    ['نور غیرمستقیم در لبهٔ سقف، سایه‌ها را نرم می‌کند و ارتفاع فضا را حفظ می‌کند.', 'Indirect light at the ceiling edge softens shadows and keeps the height of the room.'],
                ],
                'cover' => 'images/pj-lavasan.webp',
                'gallery' => ['images/pj-sabz.webp', 'images/jr-lighting.webp', 'images/col-living.webp', 'images/jr-door.webp'],
                'video' => $videoRes,
            ],
            [
                'slug' => 'pars', 'cat' => 'commercial', 'space' => 'office', 'city' => 'tehran',
                'year' => '1403', 'area' => 210, 'featured_slot' => 0,
                'title' => ['لابی دفتر پارس', 'Pars Office Lobby'],
                'client' => ['شرکت مهندسی پارس', 'Pars Engineering'],
                'duration' => ['۴ ماه', '4 months'],
                'place' => ['تهران، خیابان ولیعصر', 'Valiasr St., Tehran'],
                'lede' => ['پذیرشی آرام با دیوار سرمه‌ای، چوب و دو صندلی که مهمان را دعوت می‌کنند.', 'A calm reception with a navy wall, timber, and two chairs that invite the guest in.'],
                'text' => [
                    ['لابی پارس اولین برداشت شرکت از خودش است؛ پس مینیمال و بدون تزئین طراحی شد. پانل چوبی صدا را کنترل می‌کند.', "The Pars lobby is the company's first impression, so it was kept minimal and unadorned. A timber panel controls sound."],
                    ['مبلمان از جنس طبیعی انتخاب شد تا با گذر زمان خوش‌ظاهر بماند.', 'Furniture was chosen in natural materials that age well.'],
                ],
                'cover' => 'images/pj-pars.webp',
                'gallery' => ['images/pj-noor.webp', 'images/jr-lighting.webp', 'images/services-clinic.webp', 'images/detail.webp'],
                'video' => $videoCom,
            ],
            [
                'slug' => 'noor', 'cat' => 'commercial', 'space' => 'office', 'city' => 'amol',
                'year' => '1402', 'area' => 160, 'featured_slot' => 0,
                'title' => ['استودیو معماری نور', 'Noor Architecture Studio'],
                'client' => ['استودیو نور', 'Noor Studio'],
                'duration' => ['۵ ماه', '5 months'],
                'place' => ['آمل، دریای ۲۰', 'Darya 20, Amol'],
                'lede' => ['یک میز بلند، دیوار نمونه‌ها و نور شمال؛ فضایی برای فکر کردن با دست.', 'A long table, a wall of samples, and north light; a space for thinking with your hands.'],
                'text' => [
                    ['استودیو نور برای تیمی هشت‌نفره طراحی شد. میز بلند مرکزی هم میز جلسه است و هم میز ماکت.', 'Noor was designed for a team of eight. The long central table is both meeting table and model bench.'],
                    ['دیوار نمونه‌ها و قفسهٔ سرمه‌ای، آرشیو متریال را همیشه در دسترس نگه می‌دارد.', 'A sample wall and a navy shelf keep the material archive always in reach.'],
                ],
                'cover' => 'images/pj-noor.webp',
                'gallery' => ['images/pj-pars.webp', 'images/jr-plan.webp', 'images/detail.webp', 'images/proj-acajou.webp'],
                'video' => $videoCom,
            ],
        ];

        $created = 0;
        foreach ($projects as $idx => $p) {
            $existing = get_page_by_path($p['slug'], OBJECT, ProjectPostType::POST_TYPE);
            if ($existing instanceof \WP_Post) {
                continue;
            }

            $faParagraphs = array_map(static fn (array $pair): string => '<p>' . esc_html($pair[0]) . '</p>', $p['text']);
            $enParagraphs = array_map(static fn (array $pair): string => '<p>' . esc_html($pair[1]) . '</p>', $p['text']);

            $postId = wp_insert_post([
                'post_type' => ProjectPostType::POST_TYPE,
                'post_status' => 'publish',
                'post_name' => $p['slug'],
                'post_title' => $p['title'][0],
                'post_excerpt' => $p['lede'][0],
                'post_content' => implode("\n\n", $faParagraphs),
                'menu_order' => $idx + 1,
            ]);

            if (is_wp_error($postId) || ! $postId) {
                continue;
            }

            wp_set_object_terms($postId, [$p['cat']], ProjectPostType::TAX_CATEGORY);
            wp_set_object_terms($postId, [$p['space']], ProjectPostType::TAX_SPACE);
            wp_set_object_terms($postId, [$p['city']], ProjectPostType::TAX_CITY);

            update_post_meta($postId, '_laico_title_en', $p['title'][1]);
            update_post_meta($postId, '_laico_lede_en', $p['lede'][1]);
            update_post_meta($postId, '_laico_content_en', implode("\n\n", $enParagraphs));
            update_post_meta($postId, '_laico_client_fa', $p['client'][0]);
            update_post_meta($postId, '_laico_client_en', $p['client'][1]);
            update_post_meta($postId, '_laico_year', $p['year']);
            update_post_meta($postId, '_laico_duration_fa', $p['duration'][0]);
            update_post_meta($postId, '_laico_duration_en', $p['duration'][1]);
            update_post_meta($postId, '_laico_location_fa', $p['place'][0]);
            update_post_meta($postId, '_laico_location_en', $p['place'][1]);
            update_post_meta($postId, '_laico_area', (string) $p['area']);
            update_post_meta($postId, '_laico_video_url', $p['video']);

            if ($p['featured_slot'] > 0) {
                update_post_meta($postId, '_laico_featured_home', '1');
                update_post_meta($postId, '_laico_mosaic_slot', (string) $p['featured_slot']);
            }

            $coverAtt = $this->ensureThemeAssetAttachment($p['cover'], $p['title'][0]);
            if ($coverAtt > 0) {
                set_post_thumbnail($postId, $coverAtt);
            }

            $galleryAttIds = [];
            foreach ($p['gallery'] as $gRel) {
                $gAtt = $this->ensureThemeAssetAttachment($gRel, $p['title'][0]);
                if ($gAtt > 0) {
                    $galleryAttIds[] = $gAtt;
                }
            }
            if (! empty($galleryAttIds)) {
                update_post_meta($postId, '_laico_gallery_ids', implode(',', $galleryAttIds));
            } else {
                $urls = array_map(static fn (string $rel): string => ViteAssets::assetUrl($rel), $p['gallery']);
                update_post_meta($postId, '_laico_gallery_urls', implode("\n", $urls));
            }

            // Only projects that really have drawings get a sketch section (ready-made set bundled with the theme).
            if (! empty($p['sketch_set'])) {
                BundledSketches::attachToProject((int) $postId, (string) $p['sketch_set']);
            }

            $created++;
        }

        return $created;
    }

    private function seedJournalPosts(): int
    {
        $articles = [
            [
                'slug' => 'honest-materials', 'date' => '2026-10-12 10:00:00', 'read' => 4, 'image' => 'images/detail.webp',
                'cat' => ['متریال', 'Materials', 'materials'],
                'title' => ['متریال صادق یعنی چه؟', 'What honest material means'],
                'excerpt' => ['سنگی که سنگ است، چوبی که چوب است، و لاکی که ادای سنگ را درنمی‌آورد.', 'Stone that is stone, timber that is timber, and lacquer that does not pretend to be stone.'],
                'body_fa' => '<p>در لایکو، روکش قرار نیست چیز دیگری را تقلید کند. لاک مات، لاک است. چوب راش، چوب است. سنگ رگهٔ خودش را دارد و ما آن را صاف نمی‌کنیم تا «تمیزتر» شود.</p><h2>چرا تقلید گران تمام می‌شود</h2><p>متریال تقلیدی در روز اول شبیه اصل است، اما در سال سوم لبه‌ها، نور و لمس آن را لو می‌دهند. متریال صادق با گذر زمان زیباتر یا دست‌کم قابل‌پیش‌بینی پیر می‌شود.</p><blockquote>متریالی که ادای چیز دیگری را درنمی‌آورد، هیچ‌وقت از مد نمی‌افتد.</blockquote><h2>سه قاعده برای انتخاب</h2><p>اول، سطحی را انتخاب کنید که بتوان آن را تعمیر کرد. دوم، رگه و بافت را بخشی از طرح بدانید، نه ایراد. سوم، در هر اتاق بیش از سه متریال اصلی به کار نبرید.</p>',
                'body_en' => '<p>At Laico a finish is not there to imitate something else. Matt lacquer is lacquer. Beech is beech. Stone has its own vein and we do not flatten it to look “cleaner”.</p><h2>Why imitation costs more</h2><p>An imitation looks right on day one, but by year three the edges, the light and the touch give it away. An honest material ages better, or at least predictably.</p><blockquote>A material that does not pretend to be something else never goes out of fashion.</blockquote><h2>Three rules for choosing</h2><p>First, pick a surface that can be repaired. Second, treat vein and grain as part of the design, not a flaw. Third, never use more than three main materials in one room.</p>',
            ],
            [
                'slug' => 'invisible-joints', 'date' => '2026-09-02 10:00:00', 'read' => 3, 'image' => 'images/jr-door.webp',
                'cat' => ['ساخت', 'Craft', 'craft'],
                'title' => ['درزهایی که نباید دیده شوند', 'Joints that should not be seen'],
                'excerpt' => ['تلورانس زیر یک میلی‌متر یک شعار نیست؛ یک عدد کارگاهی است.', 'A tolerance under one millimetre is not a slogan. It is a workshop number.'],
                'body_fa' => '<p>درز، جای خطا را نشان می‌دهد. اگر دو در کنار هم بیش از یک میلی‌متر اختلاف داشته باشند، سایه آن را لو می‌دهد.</p><h2>مونتاژ آزمایشی در کارگاه</h2><p>به همین دلیل هر سیستم پیش از حمل در کارگاه کامل مونتاژ می‌شود. ترازها، لولاها و ریل‌ها همان‌جا تنظیم می‌شوند، نه در خانهٔ مشتری.</p><blockquote>درز خوب درزی است که هیچ‌کس دربارهٔ آن حرف نزند.</blockquote><h2>نصب، آخرین ده درصد</h2><p>نصب فقط جابه‌جایی نیست؛ ترازیابی نهایی و تنظیم درها با دقت لیزر انجام می‌شود تا درزها در طول یک خط ادامه پیدا کنند.</p>',
                'body_en' => '<p>A joint shows where the error is. If two doors differ by more than a millimetre, the shadow gives it away.</p><h2>A trial assembly in the workshop</h2><p>That is why every system is fully assembled in the workshop before shipping. Levels, hinges and runners are set there, not in the client’s home.</p><blockquote>A good joint is one nobody talks about.</blockquote><h2>Installation, the last ten percent</h2><p>Installation is not just moving boxes; final levelling and door adjustment are done by laser so the joints run along a single line.</p>',
            ],
            [
                'slug' => 'showroom-visit', 'date' => '2026-07-21 10:00:00', 'read' => 3, 'image' => 'images/pj-pars.webp',
                'cat' => ['شو روم', 'Showroom', 'showroom-cat'],
                'title' => ['چرا بازدید شو روم اجباری است', 'Why the showroom visit is required'],
                'excerpt' => ['رنگ روی صفحه، رنگ در اتاق نیست. پالت باید لمس شود.', 'Colour on a screen is not colour in a room. A palette has to be touched.'],
                'body_fa' => '<p>نمونهٔ لاک مات زیر نور شمال با عکس همان نمونه فرق دارد. رنگ روی صفحه، رنگ در اتاق نیست.</p><h2>پالت باید لمس شود</h2><p>در آمل، دریای ۲۰، پالت متریال را کنار هم می‌چینیم و با نور طبیعی مقایسه می‌کنیم. بعد از این بازدید، سفارش به کارگاه می‌رود.</p><blockquote>تصمیمی که با دست گرفته شود، پشیمانی کمتری دارد.</blockquote><h2>چه چیزی همراه بیاورید</h2><p>پلان یا عکس فضا، متراژ تقریبی و اگر دارید، چند تصویر مرجع. بقیه را در جلسهٔ اول با هم مشخص می‌کنیم.</p>',
                'body_en' => '<p>A matt lacquer sample under north light differs from its photograph. Colour on a screen is not colour in a room.</p><h2>The palette has to be touched</h2><p>In Amol, Darya 20, we lay the palette side by side and compare it in natural light. Only after this visit does the order go to the workshop.</p><blockquote>A decision made by hand carries less regret.</blockquote><h2>What to bring</h2><p>A plan or photos of the space, the approximate area and, if you have them, a few reference images. We settle the rest at the first meeting.</p>',
            ],
            [
                'slug' => 'light-first', 'date' => '2026-05-30 10:00:00', 'read' => 5, 'image' => 'images/jr-lighting.webp',
                'cat' => ['طراحی', 'Design', 'design'],
                'title' => ['نور، اولین متریال هر فضا', 'Light, the first material of every room'],
                'excerpt' => ['نور فقط دیدن را ممکن نمی‌کند؛ حجم، رنگ و حال‌وهوای اتاق را می‌سازد.', 'Light does not just make seeing possible; it builds the volume, colour and mood of a room.'],
                'body_fa' => '<p>نور فقط دیدن را ممکن نمی‌کند؛ حجم، رنگ و حال‌وهوای اتاق را می‌سازد. به همین دلیل طراحی نور را پیش از انتخاب مبلمان شروع می‌کنیم.</p><h2>سه لایهٔ نور</h2><p>نور عمومی نرم، نور کار برای سطوح فعال و نور تأکیدی برای بافت‌ها. هر سه باید جدا قابل کنترل باشند.</p><blockquote>اگر لامپ را ببینید، طراحی نور شکست خورده است.</blockquote><h2>رنگ نور</h2><p>برای اتاق‌های سرمه‌ای و چوبی، نور ۲۷۰۰ تا ۳۰۰۰ کلوین بهترین نتیجه را می‌دهد؛ برای آشپزخانه و حمام تا ۳۵۰۰ کلوین.</p>',
                'body_en' => '<p>Light does not just make seeing possible; it builds the volume, colour and mood of a room. That is why we start the lighting design before choosing furniture.</p><h2>Three layers of light</h2><p>Soft general light, task light for working surfaces, and accent light for textures. All three must be controlled separately.</p><blockquote>If you can see the bulb, the lighting design has failed.</blockquote><h2>The colour of light</h2><p>For navy and timber rooms, 2700 to 3000 K works best; for kitchens and bathrooms, up to 3500 K.</p>',
            ],
            [
                'slug' => 'plan-before-colour', 'date' => '2026-04-11 10:00:00', 'read' => 4, 'image' => 'images/jr-plan.webp',
                'cat' => ['طراحی', 'Design', 'design'],
                'title' => ['پلان پیش از رنگ', 'Plan before colour'],
                'excerpt' => ['رنگ آخرین تصمیم است؛ ساخت، اولین تعهد.', 'Colour is the last decision; building is the first commitment.'],
                'body_fa' => '<p>پلان، نور و مسیر کار پیش از انتخاب رنگ بسته می‌شود. رنگ آخرین تصمیم است؛ ساخت اولین تعهد.</p><h2>از مسیر شروع کنید</h2><p>قبل از انتخاب هر چیز، مسیر حرکت روزمرهٔ ساکنان را روی پلان رسم کنید: از در ورودی تا آشپزخانه، از اتاق تا حمام.</p><blockquote>یک پلان خوب رنگ بد را تحمل می‌کند؛ برعکس آن درست نیست.</blockquote><h2>چک‌لیست جلسهٔ اول</h2><p>متراژ و ارتفاع، موقعیت نور طبیعی، تأسیسات ثابت و عادت‌های روزمره. با این چهار مورد می‌توان پیش‌طرح را در یک هفته آماده کرد.</p>',
                'body_en' => '<p>Plan, light and working path are settled before colour. Colour is the last decision; building is the first commitment.</p><h2>Start with the path</h2><p>Before choosing anything, draw the residents’ daily path on the plan: from the entrance to the kitchen, from the bedroom to the bath.</p><blockquote>A good plan survives a bad colour; the reverse is not true.</blockquote><h2>First-meeting checklist</h2><p>Area and height, position of natural light, fixed services and daily habits. With these four, a concept can be ready within a week.</p>',
            ],
            [
                'slug' => 'working-kitchen', 'date' => '2026-02-12 10:00:00', 'read' => 4, 'image' => 'images/hero.webp',
                'cat' => ['آشپزخانه', 'Kitchen', 'kitchen-cat'],
                'title' => ['آشپزخانه‌ای که واقعاً کار می‌کند', 'A kitchen that actually works'],
                'excerpt' => ['آشپزخانهٔ زیبا اگر مسیر کار نداشته باشد، بعد از یک ماه خسته‌کننده می‌شود.', 'A beautiful kitchen with no working path becomes tiresome after a month.'],
                'body_fa' => '<p>آشپزخانهٔ زیبا اگر مسیر کار نداشته باشد، بعد از یک ماه خسته‌کننده می‌شود. زیبایی باید از عملکرد بیرون بیاید، نه جای آن بنشیند.</p><h2>مثلث کار، نسخهٔ امروز</h2><p>سینک، اجاق و یخچال هنوز سه نقطهٔ اصلی‌اند، اما جزیره و ایستگاه‌های کار مسیرها را تغییر داده‌اند. فاصله‌ها باید کوتاه و مسیرها بدون تقاطع باشند.</p><blockquote>جزیره یا میز کار است یا میز صبحانه؛ اگر هر دو باشد، طراحی درست است.</blockquote><h2>فضای ذخیره</h2><p>قفسه‌های باز را برای وسایل روزانه نگه دارید و بقیه را پشت درهای بدون دستگیره پنهان کنید تا سطوح آزاد بمانند.</p>',
                'body_en' => '<p>A beautiful kitchen with no working path becomes tiresome after a month. Beauty should come out of function, not replace it.</p><h2>The work triangle, today’s version</h2><p>Sink, hob and fridge are still the three anchors, but islands and work stations have changed the routes. Distances should be short and paths should not cross.</p><blockquote>An island is a worktop or a breakfast table; if it is both, the design is right.</blockquote><h2>Storage</h2><p>Keep open shelves for daily things and hide the rest behind handleless doors so surfaces stay clear.</p>',
            ],
        ];

        $created = 0;
        foreach ($articles as $a) {
            $existing = get_page_by_path($a['slug'], OBJECT, 'post');
            if ($existing instanceof \WP_Post) {
                continue;
            }

            [$catFa, $catEn, $catSlug] = $a['cat'];
            $term = get_term_by('slug', $catSlug, 'category');
            if (! $term instanceof \WP_Term) {
                $insTerm = wp_insert_term($catFa, 'category', ['slug' => $catSlug]);
                $termId = (! is_wp_error($insTerm) && ! empty($insTerm['term_id'])) ? (int) $insTerm['term_id'] : 0;
                if ($termId > 0) {
                    update_term_meta($termId, '_laico_name_en', $catEn);
                }
            } else {
                $termId = (int) $term->term_id;
            }

            $postId = wp_insert_post([
                'post_type' => 'post',
                'post_status' => 'publish',
                'post_name' => $a['slug'],
                'post_date' => $a['date'],
                'post_title' => $a['title'][0],
                'post_excerpt' => $a['excerpt'][0],
                'post_content' => $a['body_fa'],
                'post_category' => $termId > 0 ? [$termId] : [],
            ]);

            if (is_wp_error($postId) || ! $postId) {
                continue;
            }

            update_post_meta($postId, '_laico_read_time', (string) $a['read']);
            update_post_meta($postId, '_laico_title_en', $a['title'][1]);
            update_post_meta($postId, '_laico_excerpt_en', $a['excerpt'][1]);
            update_post_meta($postId, '_laico_content_en', $a['body_en']);

            $attId = $this->ensureThemeAssetAttachment($a['image'], $a['title'][0]);
            if ($attId > 0) {
                set_post_thumbnail($postId, $attId);
            }

            $created++;
        }

        return $created;
    }

    private function seedNavigationMenus(): void
    {
        $locations = get_theme_mod('nav_menu_locations', []);
        if (! is_array($locations)) {
            $locations = [];
        }

        if (empty($locations['primary_navigation'])) {
            $menuName = 'منوی اصلی لایکو';
            $menuObj = wp_get_nav_menu_object($menuName);
            $menuId = $menuObj ? (int) $menuObj->term_id : (int) wp_create_nav_menu($menuName);

            if ($menuId > 0 && empty(wp_get_nav_menu_items($menuId))) {
                $items = [
                    ['title' => 'صفحه اصلی', 'slug' => 'home'],
                    ['title' => 'خدمات', 'slug' => 'services'],
                    ['title' => 'پروژه‌ها', 'slug' => 'projects'],
                    ['title' => 'مجله', 'slug' => 'journal'],
                    ['title' => 'درباره ما', 'slug' => 'about'],
                    ['title' => 'تماس با ما', 'slug' => 'contact'],
                ];
                foreach ($items as $item) {
                    $page = get_page_by_path($item['slug'], OBJECT, 'page');
                    if ($page instanceof \WP_Post) {
                        wp_update_nav_menu_item($menuId, 0, [
                            'menu-item-title' => $item['title'],
                            'menu-item-object' => 'page',
                            'menu-item-object-id' => $page->ID,
                            'menu-item-type' => 'post_type',
                            'menu-item-status' => 'publish',
                        ]);
                    }
                }
            }

            if ($menuId > 0) {
                $locations['primary_navigation'] = $menuId;
                set_theme_mod('nav_menu_locations', $locations);
            }
        }
    }

    /**
     * Import a bundled image from `resources/images/...` into WordPress Media Library once,
     * returning its Attachment ID so `set_post_thumbnail()` and responsive `srcset` work natively.
     */
    private function ensureThemeAssetAttachment(string $relativeAsset, string $title): int
    {
        $srcFile = LAICO_THEME_DIR . '/resources/' . ltrim($relativeAsset, '/');
        if (! is_file($srcFile)) {
            return 0;
        }

        $assetKey = 'laico_asset_' . md5($relativeAsset);
        $existing = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_laico_source_asset',
            'meta_value' => $assetKey,
        ]);

        if (! empty($existing[0])) {
            return (int) $existing[0];
        }

        $uploads = wp_upload_dir();
        if (! empty($uploads['error'])) {
            return 0;
        }

        $filename = wp_unique_filename((string) $uploads['path'], basename($srcFile));
        $destPath = trailingslashit((string) $uploads['path']) . $filename;

        if (! @copy($srcFile, $destPath)) {
            return 0;
        }

        $filetype = wp_check_filetype($filename, null);
        $attachment = [
            'guid' => trailingslashit((string) $uploads['url']) . $filename,
            'post_mime_type' => $filetype['type'] ?: 'image/webp',
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attachId = wp_insert_attachment($attachment, $destPath);
        if (is_wp_error($attachId) || ! $attachId) {
            return 0;
        }

        $meta = wp_generate_attachment_metadata((int) $attachId, $destPath);
        wp_update_attachment_metadata((int) $attachId, $meta);
        update_post_meta((int) $attachId, '_laico_source_asset', $assetKey);
        update_post_meta((int) $attachId, '_wp_attachment_image_alt', $title);

        return (int) $attachId;
    }
}
