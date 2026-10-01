// API Backend pour Accueil Immo CI
// Connexion entre le frontend et le backend

class APIConnector {
    constructor() {
        this.baseURL = 'http://localhost/prodesticprojet/backend/';
        this.token = localStorage.getItem('auth_token');
    }

    // Méthode générique pour les requêtes
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        // Ajouter le token si disponible
        if (this.token) {
            config.headers.Authorization = `Bearer ${this.token}`;
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erreur API');
            }

            return data;
        } catch (error) {
            console.error('Erreur API:', error);
            throw error;
        }
    }

    // Authentification
    async login(email, password) {
        try {
            const data = await this.request('auth/login', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });
            
            // Afficher les détails de la connexion dans la console
            console.log('=== CONNEXION RÉUSSIE ===');
            console.log('Email utilisé:', email);
            console.log('Réponse API:', data);
            
            if (data.token && data.user) {
                console.log('✅ Utilisateur connecté:', data.user);
                console.log('📧 Email:', data.user.email);
                console.log('📞 Téléphone:', data.user.telephone);
                console.log('👤 Nom complet:', `${data.user.prenom} ${data.user.nom}`);
                console.log('🔑 Token généré:', data.token.substring(0, 20) + '...');
            }
            
            if (data.token) {
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                this.token = data.token;
            }
            
            return data;
        } catch (error) {
            console.error('❌ Erreur lors de la connexion:', error);
            throw error;
        }
    }

    async register(userData) {
        try {
            const response = await this.request('auth/register', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
            
            // Afficher les détails de l'inscription dans la console
            console.log('=== INSCRIPTION RÉUSSIE ===');
            console.log('Données utilisateur:', userData);
            console.log('Réponse API:', response);
            
            if (response.user) {
                console.log('✅ Utilisateur enregistré:', response.user);
                console.log('📧 Email:', response.user.email);
                console.log('📞 Téléphone:', response.user.telephone);
                console.log('👤 Nom:', response.user.nom, response.user.prenom);
            }
            
            return response;
        } catch (error) {
            console.error('❌ Erreur lors de l\'inscription:', error);
            throw error;
        }
    }

    async logout() {
        try {
            await this.request('auth/logout', { method: 'POST' });
        } finally {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            this.token = null;
        }
    }

    async checkAuth() {
        if (!this.token) return null;
        
        try {
            return await this.request('auth/check');
        } catch (error) {
            this.logout();
            return null;
        }
    }

    // Biens immobiliers
    async getProperties(filters = {}) {
        const params = new URLSearchParams(filters);
        return await this.request(`properties?${params}`);
    }

    async getProperty(id) {
        return await this.request(`properties/${id}`);
    }

    async createProperty(propertyData) {
        return await this.request('properties', {
            method: 'POST',
            body: JSON.stringify(propertyData)
        });
    }

    async updateProperty(id, propertyData) {
        return await this.request(`properties/${id}`, {
            method: 'PUT',
            body: JSON.stringify(propertyData)
        });
    }

    async deleteProperty(id) {
        return await this.request(`properties/${id}`, {
            method: 'DELETE'
        });
    }

    async searchProperties(query, filters = {}) {
        const params = new URLSearchParams({ q: query, ...filters });
        return await this.request(`properties/search?${params}`);
    }

    async getFeaturedProperties(limit = 6) {
        return await this.request(`properties/featured?limit=${limit}`);
    }

    async getMyProperties(filters = {}) {
        const params = new URLSearchParams(filters);
        return await this.request(`properties/my?${params}`);
    }

    // Upload d'images
    async uploadImages(files) {
        const formData = new FormData();
        Array.from(files).forEach(file => {
            formData.append('images[]', file);
        });

        return await this.request('properties/upload', {
            method: 'POST',
            headers: {}, // Laisser le navigateur définir le Content-Type
            body: formData
        });
    }

    // Contact
    async submitContact(contactData) {
        return await this.request('contact', {
            method: 'POST',
            body: JSON.stringify(contactData)
        });
    }

    // Conciergerie
    async getConciergeServices() {
        return await this.request('concierge/services');
    }

    async bookConciergeService(bookingData) {
        return await this.request('concierge/book', {
            method: 'POST',
            body: JSON.stringify(bookingData)
        });
    }

    // Notifications
    async getNotifications() {
        return await this.request('notifications');
    }

    async markNotificationAsRead(id) {
        return await this.request(`notifications/${id}/read`, {
            method: 'PUT'
        });
    }

    // Dashboard
    async getDashboardStats() {
        return await this.request('dashboard/stats');
    }

    async getRecentActivity() {
        return await this.request('dashboard/recent');
    }

    async getRevenueData(period = 'month') {
        return await this.request(`dashboard/revenue?period=${period}`);
    }
}

// Initialiser l'API
const api = new APIConnector();

// Fonctions utilitaires pour le frontend
class UIHelper {
    static showLoading(element) {
        if (element) {
            element.innerHTML = '<div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>';
        }
    }

    static showError(element, message) {
        if (element) {
            element.innerHTML = `<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">${message}</div>`;
        }
    }

    static showSuccess(element, message) {
        if (element) {
            element.innerHTML = `<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">${message}</div>`;
        }
    }

    static formatPrice(price) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'XOF',
            minimumFractionDigits: 0
        }).format(price);
    }

    static formatDate(date) {
        return new Date(date).toLocaleDateString('fr-FR');
    }

    static truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
}

// Gestion de l'authentification
class AuthManager {
    static isLoggedIn() {
        return !!localStorage.getItem('auth_token');
    }

    static getCurrentUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    }

    static updateUserUI() {
        const user = this.getCurrentUser();
        const loginLinks = document.querySelectorAll('.auth-login');
        const logoutLinks = document.querySelectorAll('.auth-logout');
        const userMenu = document.querySelectorAll('.auth-user');

        if (user) {
            loginLinks.forEach(link => link.style.display = 'none');
            logoutLinks.forEach(link => link.style.display = 'block');
            userMenu.forEach(menu => {
                menu.style.display = 'block';
                menu.querySelector('.user-name').textContent = `${user.prenom} ${user.nom}`;
            });
        } else {
            loginLinks.forEach(link => link.style.display = 'block');
            logoutLinks.forEach(link => link.style.display = 'none');
            userMenu.forEach(menu => menu.style.display = 'none');
        }
    }

    static async logout() {
        try {
            await api.logout();
            window.location.href = 'seconnecter.html';
        } catch (error) {
            console.error('Erreur logout:', error);
            window.location.href = 'seconnecter.html';
        }
    }
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour l'UI en fonction de l'authentification
    AuthManager.updateUserUI();

    // Ajouter les écouteurs d'événements communs
    this.addEventListener('click', function(e) {
        // Gérer les liens de déconnexion
        if (e.target.matches('.logout-btn')) {
            e.preventDefault();
            AuthManager.logout();
        }

        // Gérer les liens protégés
        if (e.target.matches('.protected-link')) {
            if (!AuthManager.isLoggedIn()) {
                e.preventDefault();
                window.location.href = 'seconnecter.html';
            }
        }
    });
});

// Exporter pour utilisation globale
window.api = api;
window.UIHelper = UIHelper;
window.AuthManager = AuthManager;
