/**
 * Парсер отзывов Яндекс.Карт через Puppeteer.
 *
 * Почему не HTTP: Яндекс видит серверные запросы и кидает капчу.
 * Headless Chrome с антидетектом притворяется живым пользователем —
 * скроллит страницу, ждёт подгрузки, вытягивает всё из DOM.
 *
 * Вызывается из PHP через Process::run(['node', 'yandex-parser.js', url]).
 * Результат — JSON в stdout. Ошибки — JSON с полем error в stderr.
 *
 * Если Яндекс поменяет вёрстку — править селекторы здесь,
 * PHP-код трогать не придётся.
 */

import puppeteer from 'puppeteer';

const url = process.argv[2];
if (!url) { console.error(JSON.stringify({error:'URL required'})); process.exit(1); }

// На всякий случай: если юзер дал ссылку без /reviews/ — добавим
const baseUrl = url.replace(/\/+$/, '');
const reviewsUrl = baseUrl.includes('/reviews/') ? baseUrl : `${baseUrl}/reviews/`;

async function parse() {
    // Запускаем Chromium с флагами для Docker и антидетекта.
    // --no-proxy-server потому что WSL-прокси ломает запросы к Яндексу.
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox','--disable-setuid-sandbox','--disable-dev-shm-usage','--disable-blink-features=AutomationControlled','--no-proxy-server','--window-size=1920,1080'],
        env: {http_proxy:'',https_proxy:'',HTTP_PROXY:'',HTTPS_PROXY:''},
    });

    const page = await browser.newPage();
    // Прикидываемся Win10 Chrome 125 — самый обычный конфиг
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/125.0.0.0 Safari/537.36');
    await page.setViewport({width:1920, height:1080});
    // Прячем webdriver-флаг, иначе Яндекс спалит
    await page.evaluateOnNewDocument(() => { Object.defineProperty(navigator,'webdriver',{get:()=>false}); });

    try {
        await page.goto(reviewsUrl, {waitUntil:'networkidle2', timeout:30000});
        await page.waitForSelector('h1, [class*="business-reviews"], [class*="card-reviews"]', {timeout:10000});
        // Даём странице дорисоваться после начальной загрузки
        await new Promise(r => setTimeout(r, 2000));

        await loadAllReviews(page);
        const data = await extractData(page);
        await browser.close();
        return data;
    } catch(e) { await browser.close(); throw e; }
}

/**
 * Скроллим пока не перестанут подгружаться новые отзывы.
 * Яндекс подгружает их лениво по мере прокрутки —
 * имитируем поведение живого пользователя.
 *
 * До 60 итераций (хватит на ~600 отзывов), задержки 400-1000ms.
 * Останавливаемся если высота страницы не менялась 9 раз подряд.
 */
async function loadAllReviews(page) {
    for (let i = 0; i < 60; i++) {
        const scrolled = await page.evaluate(() => {
            // Ищем скролл-контейнер с отзывами (у Яндекса их несколько)
            const selectors = [
                '[class*="scroll__"]',
                '[class*="scroll-container"]',
                '[class*="reviews-list"]',
                '.card-reviews-view',
                '[class*="card-reviews"]',
            ];
            let container = null;
            for (const sel of selectors) {
                container = document.querySelector(sel);
                if (container && container.scrollHeight > container.clientHeight) break;
            }
            if (!container) container = document.scrollingElement || document.documentElement;

            const prev = container.scrollHeight;
            container.scrollTo({ top: container.scrollHeight, behavior: 'instant' });
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'instant' });
            return prev;
        });

        // Случайная задержка — человек не скроллит с идеальным интервалом
        await new Promise(r => setTimeout(r, 400 + Math.random() * 600));

        const newHeight = await page.evaluate(() => {
            const el = document.querySelector('[class*="scroll__"]') || document.scrollingElement || document.documentElement;
            return el.scrollHeight;
        });

        if (newHeight === scrolled && i > 8) break;
    }
    await new Promise(r => setTimeout(r, 2000));
}

/**
 * Вытягиваем всё что нашли из DOM.
 *
 * Селекторы завязаны на конкретную вёрстку Яндекса (июнь 2026).
 * Если сломается — вот что искать в DevTools:
 *  - h1                                        → название организации
 *  - [aria-label*="Оценка"]                    → средний рейтинг
 *  - .business-rating-amount-view._summary     → «36 оценок»
 *  - .business-reviews-card-view__review       → карточка одного отзыва
 *  - [class*="view__info"]                     → автор + дата
 *  - [class*="view__body"]                     → текст отзыва
 */
async function extractData(page) {
    return await page.evaluate(() => {
        const body = document.body.textContent || '';

        // --- Name ---
        const h1 = document.querySelector('h1');
        const name = h1 ? h1.textContent.trim() : '';

        // --- Rating ---
        let average_rating = null;
        const aria = document.querySelector('[aria-label*="Оценка"], [aria-label*="Рейтинг"]');
        if (aria) {
            const m = (aria.getAttribute('aria-label')||'').match(/(\d+[.,]\d+)/);
            if (m) average_rating = parseFloat(m[1].replace(',','.'));
        }
        if (average_rating) average_rating = Math.round(average_rating * 10) / 10;

        // --- Rating count — сколько всего оценок (не отзывов!) ---
        let rating_count = 0;
        const ratingCountEl = document.querySelector(
            '.business-rating-amount-view._summary, ' +
            '[class*="rating-count"], ' +
            '[class*="stars-and-count"]'
        );
        if (ratingCountEl) {
            const m = (ratingCountEl.textContent || '').match(/(\d+)/);
            if (m) rating_count = parseInt(m[1]);
        }
        // Запасной вариант: ищем «N оценок» по всему body,
        // берём минимальное число (рейтинг «4,4» + «36 оценок» склеиваются в «4,436»)
        if (!rating_count) {
            const matches = [...body.matchAll(/(\d+)\s*оцен[ое][кk]/gi)];
            if (matches.length) {
                rating_count = Math.min(...matches.map(m => parseInt(m[1])).filter(n => n > 0 && n < 1000000));
            }
        }

        // --- Review count — количество текстовых отзывов ---
        let review_count = 0;
        const metaDescEl = document.querySelector('meta[name="description"]');
        if (metaDescEl) {
            const metaContent = metaDescEl.getAttribute('content') || '';
            const m = metaContent.match(/(\d+)\s*отзыв/i);
            if (m) review_count = parseInt(m[1]);
        }
        if (!review_count) {
            const matches = [...body.matchAll(/(\d+)\s*отзыв[а-я]*/gi)];
            if (matches.length) {
                const counts = matches.map(m => parseInt(m[1]));
                review_count = Math.min(...counts.filter(n => n > 0));
            }
        }

        // --- Extract reviews from cards ---
        const reviews = [];
        const seen = new Set();

        // Основной контейнер отзыва у Яндекса
        const reviewCards = document.querySelectorAll('.business-reviews-card-view__review');
        reviewCards.forEach(card => {
            // В info лежит всё склеенное: {автор}{знаток...}{подписаться}{дата}{начало текста}
            const infoEl = card.querySelector('[class*="view__info"]');
            if (!infoEl) return;
            const infoText = (infoEl.textContent || '').trim();
            if (infoText.length < 20) return;

            // Имя — всё что до слова «Знаток»
            let author_name = 'Аноним';
            const znatokIdx = infoText.indexOf('Знаток');
            if (znatokIdx > 0) {
                author_name = infoText.slice(0, znatokIdx).trim();
            }

            // Дата в русском формате: «7 августа 2023»
            let review_date = null;
            const dateMatch = infoText.match(/(\d{1,2}\s+(?:января|февраля|марта|апреля|мая|июня|июля|августа|сентября|октября|ноября|декабря)\s+\d{4})/i);
            if (dateMatch) review_date = dateMatch[1];

            // Текст — из body, либо вырезаем из info после даты
            const bodyEl = card.querySelector('[class*="view__body"]');
            let text = bodyEl ? bodyEl.textContent.trim() : '';
            if (!text && review_date) {
                const dateIdx = infoText.indexOf(review_date);
                if (dateIdx > 0) {
                    text = infoText.slice(dateIdx + review_date.length).trim();
                    text = text.replace(/Посмотреть ответ.*$/s, '').trim();
                }
            }
            if (!text) text = infoText;

            // Звёзды: считаем _full (целая) и _half (половинчатая)
            let rating = 0;
            card.querySelectorAll('[class*="rating"] [class*="star"], svg[class*="star"], span[class*="star"]').forEach(s => {
                const cls = s.className.baseVal || s.className || '';
                if (cls.includes('_full')) rating++;
                else if (cls.includes('_half')) rating += 0.5;
            });

            // Аватар — выдираем URL из background-image инлайнового стиля
            let author_avatar = null;
            const avatarLink = card.querySelector('[class*="user-icon"]');
            if (avatarLink) {
                const icon = avatarLink.querySelector('[class*="icon"]');
                if (icon) {
                    const style = icon.getAttribute('style') || '';
                    const urlMatch = style.match(/url\(["\x27]?([^")\x27]+)["\x27]?\)/);
                    if (urlMatch) author_avatar = urlMatch[1];
                }
            }

            const key = `${author_name}-${text.slice(0,60)}`;
            if (seen.has(key) || text.length < 10) return;
            seen.add(key);

            reviews.push({
                // Синтетический но стабильный ID: хеш от автора+даты+текста.
                // При репарсинге тот же отзыв получит тот же ID — upsert сработает корректно.
                yandex_review_id: `yrev-${author_name.slice(0,10)}-${(review_date||'').slice(0,10)}-${text.slice(0,40)}`.replace(/[^a-zA-Zа-яА-Я0-9-]/g, '-').replace(/-{2,}/g, '-').slice(0, 127),
                author_name,
                author_avatar,
                rating,
                text,
                review_date,
            });
        });

        // --- Address — из meta description: «...о „Корейский Мастер“, Саратов, Пензенская улица, 7...» ---
        let address = null;
        if (metaDescEl) {
            const metaContent = metaDescEl.getAttribute('content') || '';
            let addrMatch = metaContent.match(/о\s*«[^»]+»,\s*([^.]+?\d+)/);
            if (addrMatch) address = addrMatch[1].trim();
            if (!address) {
                addrMatch = metaContent.match(/([^.,\d]+\s+(?:ул\.?|улица|просп\.?|проспект|шоссе|наб\.?|пер\.?|площадь)[^.,]*\d+)/i);
                if (addrMatch) address = addrMatch[1].trim();
            }
        }

        return { name, address, average_rating, rating_count, review_count, reviews };
    });
}

parse()
    .then(data => console.log(JSON.stringify(data)))
    .catch(err => { console.error(JSON.stringify({error:err.message||'Parsing failed'})); process.exit(1); });
