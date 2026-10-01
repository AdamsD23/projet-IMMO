/**
 * Accueil Immo — socle commun : API, session, en-tête/pied de page, cartes d'annonces,
 * favoris, notifications, fenêtres et assistant WhatsApp.
 */
(function () {
    'use strict';

    const TOKEN_KEY = 'immo_token';
    const USER_KEY = 'immo_user';
    const FAV_KEY = 'immo_favoris';
    let WHATSAPP = '2250767418701';

    const COMMUNES = {
        cocody: 'Cocody', plateau: 'Plateau', marcory: 'Marcory', riviera: 'Riviera', yopougon: 'Yopougon',
        treichville: 'Treichville', bingerville: 'Bingerville', assinie: 'Assinie', 'grand-bassam': 'Grand-Bassam', autre: 'Autre'
    };
    const TYPES = { appartement: 'Appartement', villa: 'Villa', studio: 'Studio', duplex: 'Duplex', terrain: 'Terrain', bureau: 'Bureau' };
    const EQUIPEMENTS = {
        meuble: ['Meublé', 'chair'], climatisation: ['Climatisation', 'ac_unit'], parking: ['Parking', 'local_parking'],
        piscine: ['Piscine', 'pool'], gardiennage: ['Gardiennage 24/7', 'shield'], groupe_electrogene: ['Groupe électrogène', 'bolt']
    };
    const PROFILS = { proprietaire: 'Propriétaire', agent: 'Agent immobilier', locataire: 'Locataire', admin: 'Administrateur' };

    // ---------- Outils ----------
    const store = {
        get(k) { try { return localStorage.getItem(k); } catch { return null; } },
        set(k, v) { try { localStorage.setItem(k, v); } catch { /* indisponible */ } },
        remove(k) { try { localStorage.removeItem(k); } catch { /* indisponible */ } }
    };

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    const nf = new Intl.NumberFormat('fr-FR');
    const number = n => nf.format(Math.round(Number(n) || 0)).replace(/ /g, ' ');
    function price(p) {
        const amount = `${number(p.prix)} FCFA`;
        return p.statut_bien === 'location' ? `${amount}<span class="text-sm font-semibold opacity-70"> / mois</span>` : amount;
    }
    function shortPrice(n) {
        if (n >= 1e9) return `${(n / 1e9).toLocaleString('fr-FR', { maximumFractionDigits: 1 })} Md`;
        if (n >= 1e6) return `${(n / 1e6).toLocaleString('fr-FR', { maximumFractionDigits: 1 })} M`;
        return number(n);
    }
    function date(str) {
        if (!str) return '—';
        const d = new Date(String(str).replace(' ', 'T') + (String(str).length > 10 ? 'Z' : ''));
        return isNaN(d) ? '—' : d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
    }
    function timeAgo(str) {
        const d = new Date(String(str).replace(' ', 'T') + 'Z');
        const days = Math.floor((Date.now() - d) / 86400000);
        if (isNaN(days)) return '';
        if (days <= 0) return "aujourd'hui";
        if (days === 1) return 'hier';
        if (days < 30) return `il y a ${days} jours`;
        return `le ${date(str)}`;
    }
    const debounce = (fn, ms = 300) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
    const params = () => new URLSearchParams(location.search);
    const waLink = text => `https://wa.me/${WHATSAPP}?text=${encodeURIComponent(text)}`;

    // ---------- Session ----------
    const getToken = () => store.get(TOKEN_KEY);
    function getUser() { try { return JSON.parse(store.get(USER_KEY)); } catch { return null; } }
    function saveSession(token, user) { store.set(TOKEN_KEY, token); store.set(USER_KEY, JSON.stringify(user)); }
    function logout() { store.remove(TOKEN_KEY); store.remove(USER_KEY); location.href = 'index.html'; }

    // ---------- API ----------
    async function api(path, { method = 'GET', body, form } = {}) {
        const headers = {};
        if (getToken()) headers.Authorization = `Bearer ${getToken()}`;
        if (body !== undefined) headers['Content-Type'] = 'application/json';
        let res;
        try {
            res = await fetch(`api/${path}`, { method, headers, body: form || (body !== undefined ? JSON.stringify(body) : undefined) });
        } catch {
            throw new Error('Impossible de joindre le serveur. Vérifiez votre connexion.');
        }
        const data = await res.json().catch(() => ({}));
        if (res.status === 401 && getToken() && !path.startsWith('auth/login')) {
            store.remove(TOKEN_KEY); store.remove(USER_KEY);
            location.href = `connexion.html?retour=${encodeURIComponent(location.pathname.split('/').pop() + location.search)}&message=${encodeURIComponent('Votre session a expiré, reconnectez-vous.')}`;
        }
        if (!res.ok) throw new Error(data.error || `Erreur ${res.status}`);
        return data;
    }

    /** Pages réservées aux membres : renvoie l'utilisateur ou redirige vers la connexion */
    async function requireAuth() {
        if (!getToken()) {
            location.href = `connexion.html?retour=${encodeURIComponent(location.pathname.split('/').pop() + location.search)}`;
            return new Promise(() => {});
        }
        const user = await api('auth/me');
        store.set(USER_KEY, JSON.stringify(user));
        return user;
    }

    // ---------- Favoris (enregistrés dans le navigateur) ----------
    const favorites = {
        list() { try { return JSON.parse(store.get(FAV_KEY)) || []; } catch { return []; } },
        has(id) { return this.list().includes(Number(id)); },
        toggle(id) {
            id = Number(id);
            const list = this.list();
            const next = list.includes(id) ? list.filter(x => x !== id) : [...list, id];
            store.set(FAV_KEY, JSON.stringify(next));
            updateFavCount();
            return next.includes(id);
        }
    };
    function updateFavCount() {
        const n = favorites.list().length;
        document.querySelectorAll('[data-fav-count]').forEach(el => { el.textContent = n; el.classList.toggle('hidden', n === 0); });
    }

    // ---------- Notifications ----------
    function toast(message, type = 'success') {
        let box = document.getElementById('toasts');
        if (!box) {
            box = Object.assign(document.createElement('div'), { id: 'toasts', className: 'fixed bottom-5 left-5 right-5 sm:left-auto z-[100] flex flex-col items-end gap-2 pointer-events-none' });
            box.setAttribute('aria-live', 'polite');
            document.body.appendChild(box);
        }
        const el = document.createElement('div');
        el.className = `pop-in pointer-events-auto flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold text-white shadow-xl max-w-sm ${type === 'error' ? 'bg-red-600' : 'bg-forest'}`;
        el.innerHTML = `<span class="material-symbols-outlined icon-fill">${type === 'error' ? 'error' : 'check_circle'}</span><span>${esc(message)}</span>`;
        box.appendChild(el);
        setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, type === 'error' ? 5000 : 3000);
    }

    function modal(title, html, { size = 'max-w-lg' } = {}) {
        const wrap = document.createElement('div');
        wrap.className = 'fade-in fixed inset-0 z-[90] bg-forest/40 backdrop-blur-sm flex items-end sm:items-center justify-center sm:p-4';
        wrap.innerHTML = `<div class="pop-in bg-white w-full ${size} rounded-t-4xl sm:rounded-4xl shadow-2xl max-h-[92vh] overflow-y-auto" role="dialog" aria-modal="true" aria-label="${esc(title)}">
            <div class="flex items-center justify-between px-6 pt-6"><h2 class="text-xl font-extrabold text-forest">${esc(title)}</h2>
            <button type="button" data-close class="w-10 h-10 rounded-full hover:bg-sand flex items-center justify-center" aria-label="Fermer"><span class="material-symbols-outlined">close</span></button></div>
            <div class="p-6">${html}</div></div>`;
        const close = () => { wrap.remove(); document.removeEventListener('keydown', onKey); };
        const onKey = e => { if (e.key === 'Escape') close(); };
        wrap.addEventListener('click', e => { if (e.target === wrap || e.target.closest('[data-close]')) close(); });
        document.addEventListener('keydown', onKey);
        document.body.appendChild(wrap);
        setTimeout(() => wrap.querySelector('input, select, textarea, button:not([data-close])')?.focus(), 50);
        return { el: wrap, close };
    }

    function confirmDialog(message, label = 'Confirmer') {
        return new Promise(resolve => {
            const m = modal('Confirmation', `<p class="text-forest/70">${esc(message)}</p>
                <div class="flex justify-end gap-2 mt-6"><button type="button" data-close class="px-5 py-3 rounded-full font-bold hover:bg-sand">Annuler</button>
                <button type="button" data-ok class="px-5 py-3 rounded-full font-bold bg-red-600 text-white hover:bg-red-700">${esc(label)}</button></div>`, { size: 'max-w-md' });
            m.el.querySelector('[data-ok]').addEventListener('click', () => { m.close(); resolve(true); });
            m.el.addEventListener('click', e => { if (e.target === m.el || e.target.closest('[data-close]')) resolve(false); });
        });
    }

    // ---------- Carte d'annonce ----------
    function card(p) {
        const img = p.images?.[0]?.url || 'assets/img/abidjan-skyline.jpg';
        const fav = favorites.has(p.id);
        const facts = [
            p.chambres ? `<span class="flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">bed</span>${p.chambres} ch.</span>` : '',
            p.salles_bain ? `<span class="flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">bathtub</span>${p.salles_bain} sdb</span>` : '',
            p.surface ? `<span class="flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">square_foot</span>${number(p.surface)} m²</span>` : ''
        ].join('');
        return `<article class="property-card group relative bg-white rounded-4xl overflow-hidden shadow-sm hover:shadow-xl transition-shadow border border-forest/5">
            <a href="annonce.html?id=${p.id}" class="block relative aspect-[4/3] overflow-hidden bg-sand">
                <img src="${esc(img)}" alt="${esc(p.titre)}" loading="lazy" class="w-full h-full object-cover">
                <div class="absolute top-4 left-4 flex flex-wrap gap-2">
                    <span class="px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider ${p.statut_bien === 'vente' ? 'bg-primary text-white' : 'bg-forest text-white'}">${p.statut_bien === 'vente' ? 'À vendre' : 'À louer'}</span>
                    ${p.featured ? '<span class="px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-white text-forest">Coup de cœur</span>' : ''}
                </div>
            </a>
            <button type="button" data-fav="${p.id}" aria-pressed="${fav}" aria-label="${fav ? 'Retirer des favoris' : 'Ajouter aux favoris'}"
                class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-white/90 backdrop-blur flex items-center justify-center shadow ${fav ? 'text-red-500' : 'text-forest hover:text-primary'}">
                <span class="material-symbols-outlined ${fav ? 'icon-fill' : ''}">favorite</span>
            </button>
            <div class="p-6 pt-3">
                <p class="text-xs font-bold uppercase tracking-wider text-primary">${esc(TYPES[p.type_bien] || p.type_bien)}</p>
                <h3 class="mt-1 text-lg font-extrabold text-forest leading-snug line-clamp-2"><a href="annonce.html?id=${p.id}" class="hover:text-primary">${esc(p.titre)}</a></h3>
                <p class="mt-1 flex items-center gap-1 text-sm text-forest/60"><span class="material-symbols-outlined text-[18px]">location_on</span>${esc(COMMUNES[p.commune] || p.commune)}${p.quartier ? ' · ' + esc(p.quartier) : ''}</p>
                <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm text-forest/70">${facts}</div>
                <p class="mt-4 pt-4 border-t border-forest/10 text-xl font-extrabold text-forest">${price(p)}</p>
            </div>
        </article>`;
    }

    function skeletonCards(n = 6) {
        return Array.from({ length: n }, () => `<div class="rounded-4xl overflow-hidden bg-white border border-forest/5">
            <div class="skeleton aspect-[4/3]"></div><div class="p-6 space-y-3"><div class="skeleton h-4 w-1/3 rounded"></div><div class="skeleton h-5 w-4/5 rounded"></div><div class="skeleton h-4 w-1/2 rounded"></div></div></div>`).join('');
    }

    // Clic sur un cœur, partout sur le site
    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-fav]');
        if (!btn) return;
        e.preventDefault();
        const on = favorites.toggle(btn.dataset.fav);
        btn.setAttribute('aria-pressed', on);
        btn.className = btn.className.replace(/text-red-500|text-forest hover:text-primary/g, '') + (on ? ' text-red-500' : ' text-forest hover:text-primary');
        btn.querySelector('span').classList.toggle('icon-fill', on);
        toast(on ? 'Ajouté à vos favoris' : 'Retiré de vos favoris');
    });

    // ---------- En-tête et pied de page ----------
    function header(active) {
        const user = getUser();
        const link = (href, label, key) => `<a href="${href}" class="relative py-2 font-bold ${active === key ? 'text-primary' : 'text-forest hover:text-primary'} transition-colors">${label}</a>`;
        const account = user
            ? `<a href="espace.html" class="flex items-center gap-2 font-bold text-forest hover:text-primary"><span class="w-9 h-9 rounded-full bg-forest text-white text-sm flex items-center justify-center">${esc((user.prenom[0] || '') + (user.nom[0] || ''))}</span><span class="hidden xl:inline">Mon espace</span></a>`
            : `<a href="connexion.html" class="flex items-center gap-2 font-bold text-forest hover:text-primary"><span class="material-symbols-outlined">account_circle</span>Se connecter</a>`;
        return `<header class="sticky top-0 z-50 bg-cream/90 backdrop-blur-xl border-b border-forest/5">
            <div class="max-w-7xl mx-auto px-5 lg:px-8 h-20 flex items-center justify-between gap-6">
                <a href="index.html" class="flex items-center gap-3 shrink-0" aria-label="Accueil Immo, page d'accueil">
                    <span class="w-11 h-11 rounded-2xl bg-forest flex items-center justify-center shadow-lg rotate-[-4deg]"><span class="material-symbols-outlined text-primary icon-fill">apartment</span></span>
                    <span class="leading-none"><span class="block text-xl font-extrabold italic text-forest">ACCUEIL<span class="text-primary">IMMO</span></span><span class="block text-[10px] font-bold tracking-[.25em] text-forest/60">URBAN ENERGY</span></span>
                </a>
                <nav class="hidden lg:flex items-center gap-8" aria-label="Navigation principale">
                    ${link('annonces.html?statut=location', 'Louer', 'location')}
                    ${link('annonces.html?statut=vente', 'Acheter', 'vente')}
                    ${link('conciergerie.html', 'Conciergerie', 'conciergerie')}
                    ${link('annonces.html?favoris=1', 'Favoris <span data-fav-count class="hidden ml-1 px-1.5 rounded-full bg-primary text-white text-[11px]">0</span>', 'favoris')}
                </nav>
                <div class="hidden lg:flex items-center gap-5">
                    ${account}
                    <a href="publier.html" class="px-6 py-3 rounded-full bg-primary hover:bg-primary-dark text-white font-extrabold shadow-lg shadow-primary/30 transition">Publier une annonce</a>
                </div>
                <button type="button" id="menu-btn" class="lg:hidden w-11 h-11 rounded-2xl bg-white shadow flex items-center justify-center" aria-label="Ouvrir le menu" aria-expanded="false"><span class="material-symbols-outlined">menu</span></button>
            </div>
            <div id="mobile-nav" class="lg:hidden fixed inset-y-0 right-0 w-80 max-w-[85vw] bg-cream shadow-2xl p-6 flex flex-col gap-2 z-50">
                <button type="button" id="menu-close" class="self-end w-11 h-11 rounded-2xl bg-white shadow flex items-center justify-center" aria-label="Fermer le menu"><span class="material-symbols-outlined">close</span></button>
                <a href="annonces.html?statut=location" class="py-3 text-lg font-bold text-forest border-b border-forest/10">Louer</a>
                <a href="annonces.html?statut=vente" class="py-3 text-lg font-bold text-forest border-b border-forest/10">Acheter</a>
                <a href="conciergerie.html" class="py-3 text-lg font-bold text-forest border-b border-forest/10">Conciergerie</a>
                <a href="annonces.html?favoris=1" class="py-3 text-lg font-bold text-forest border-b border-forest/10">Mes favoris</a>
                <a href="${user ? 'espace.html' : 'connexion.html'}" class="py-3 text-lg font-bold text-forest border-b border-forest/10">${user ? 'Mon espace' : 'Se connecter'}</a>
                <a href="publier.html" class="mt-4 py-4 rounded-full bg-primary text-white text-center font-extrabold">Publier une annonce</a>
            </div>
        </header>`;
    }

    function footer() {
        return `<footer class="bg-forest text-white/80 mt-24">
            <div class="max-w-7xl mx-auto px-5 lg:px-8 py-16 grid gap-10 md:grid-cols-4">
                <div class="md:col-span-2">
                    <p class="text-2xl font-extrabold italic text-white">ACCUEIL<span class="text-primary">IMMO</span></p>
                    <p class="mt-3 max-w-sm">Location, vente et gestion de biens immobiliers à Abidjan et sur le littoral ivoirien.</p>
                    <a href="${waLink('Bonjour Accueil Immo, j\'ai une question.')}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 mt-6 px-5 py-3 rounded-full bg-white/10 hover:bg-white/20 font-bold text-white"><span class="material-symbols-outlined">chat</span>Nous écrire sur WhatsApp</a>
                </div>
                <div><p class="font-extrabold text-white mb-3">Explorer</p><ul class="space-y-2">
                    <li><a href="annonces.html?statut=location" class="hover:text-primary">Biens à louer</a></li>
                    <li><a href="annonces.html?statut=vente" class="hover:text-primary">Biens à vendre</a></li>
                    <li><a href="conciergerie.html" class="hover:text-primary">Conciergerie</a></li>
                    <li><a href="publier.html" class="hover:text-primary">Publier une annonce</a></li></ul></div>
                <div><p class="font-extrabold text-white mb-3">Informations</p><ul class="space-y-2">
                    <li><a href="mentions-legales.html" class="hover:text-primary">Mentions légales</a></li>
                    <li><a href="confidentialite.html" class="hover:text-primary">Confidentialité</a></li>
                    <li><a href="connexion.html" class="hover:text-primary">Espace propriétaire</a></li></ul></div>
            </div>
            <div class="border-t border-white/10"><p class="max-w-7xl mx-auto px-5 lg:px-8 py-6 text-sm text-white/50">© ${new Date().getFullYear()} Accueil Immo — projet de démonstration réalisé par Adams Diarra.</p></div>
        </footer>`;
    }

    // ---------- Assistant (questions fréquentes + WhatsApp) ----------
    function assistant() {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fixed bottom-5 right-5 z-40 w-16 h-16 rounded-full bg-primary text-white shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-105 transition';
        btn.setAttribute('aria-label', 'Ouvrir l\'assistant');
        btn.innerHTML = '<span class="material-symbols-outlined text-3xl">chat</span><span class="absolute top-1 right-1 w-3.5 h-3.5 rounded-full bg-accent border-2 border-white"></span>';
        document.body.appendChild(btn);

        const answers = {
            louer: ['Je cherche à louer', 'Très bien ! Voici nos biens à louer. Vous pouvez filtrer par commune, budget et équipements.', 'annonces.html?statut=location', 'Voir les locations'],
            acheter: ['Je veux acheter', 'Découvrez nos villas, appartements et terrains à vendre à Abidjan et sur le littoral.', 'annonces.html?statut=vente', 'Voir les biens à vendre'],
            publier: ['Je veux publier mon bien', 'Créez un compte propriétaire gratuit, puis publiez votre annonce avec photos en quelques minutes.', 'publier.html', 'Publier une annonce'],
            conciergerie: ['Services de conciergerie', 'Transfert aéroport, ménage, gestion locative, déménagement… Réservez en ligne.', 'conciergerie.html', 'Voir les services']
        };
        btn.addEventListener('click', () => {
            const m = modal('Comment pouvons-nous vous aider ?', `
                <div data-answer class="hidden mb-5 rounded-3xl bg-sand p-5"></div>
                <div class="grid gap-2">${Object.entries(answers).map(([k, a]) => `<button type="button" data-q="${k}" class="text-left px-5 py-4 rounded-2xl border border-forest/10 hover:border-primary font-bold text-forest">${a[0]}</button>`).join('')}</div>
                <a href="${waLink('Bonjour Accueil Immo, j\'aimerais parler à un conseiller.')}" target="_blank" rel="noopener" class="mt-4 flex items-center justify-center gap-2 py-4 rounded-full bg-[#25D366] text-white font-extrabold"><span class="material-symbols-outlined">chat</span>Parler à un conseiller sur WhatsApp</a>`, { size: 'max-w-md' });
            m.el.addEventListener('click', e => {
                const q = e.target.closest('[data-q]');
                if (!q) return;
                const [, text, href, label] = answers[q.dataset.q];
                const box = m.el.querySelector('[data-answer]');
                box.innerHTML = `<p class="text-forest">${text}</p><a href="${href}" class="inline-flex items-center gap-1 mt-3 font-extrabold text-primary">${label}<span class="material-symbols-outlined">arrow_forward</span></a>`;
                box.classList.remove('hidden');
            });
        });
    }

    /** Monte l'en-tête, le pied de page et l'assistant. data-page sur <body> indique le lien actif. */
    function layout() {
        const active = document.body.dataset.page || '';
        document.getElementById('site-header')?.insertAdjacentHTML('afterbegin', header(active));
        document.getElementById('site-footer')?.insertAdjacentHTML('afterbegin', footer());
        const nav = document.getElementById('mobile-nav');
        const menuBtn = document.getElementById('menu-btn');
        menuBtn?.addEventListener('click', () => { nav.classList.add('open'); menuBtn.setAttribute('aria-expanded', 'true'); });
        document.getElementById('menu-close')?.addEventListener('click', () => { nav.classList.remove('open'); menuBtn.setAttribute('aria-expanded', 'false'); });
        if (!document.body.hasAttribute('data-no-assistant')) assistant();
        updateFavCount();
        api('meta').then(m => { if (m.whatsapp) WHATSAPP = m.whatsapp; }).catch(() => {});
    }

    window.Immo = {
        api, requireAuth, getUser, saveSession, logout, getToken,
        toast, modal, confirm: confirmDialog,
        card, skeletonCards, favorites,
        esc, number, price, shortPrice, date, timeAgo, debounce, params, waLink: text => waLink(text),
        COMMUNES, TYPES, EQUIPEMENTS, PROFILS,
        layout
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', layout); else layout();
})();
