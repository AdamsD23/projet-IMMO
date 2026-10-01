// Chatbot JavaScript pour Accueil Immo CI
// Intégration facile dans toutes les pages

class ChatBot {
    constructor() {
        this.isOpen = false;
        this.isTyping = false;
        this.messages = [];
        this.init();
    }

    init() {
        this.createChatbotHTML();
        this.setupEventListeners();
        this.loadStyles();
    }

    createChatbotHTML() {
        const chatbotHTML = `
            <!-- Bouton du Chatbot -->
            <div id="chatbotButton" class="fixed bottom-6 right-6 z-50">
                <button onclick="chatBot.toggleChatbot()" class="bg-primary text-white p-4 rounded-full shadow-2xl hover:scale-110 transition-all duration-300 group">
                    <span class="material-symbols-outlined text-3xl group-hover:rotate-12 transition-transform">chat</span>
                    <span class="absolute -top-1 -right-1 w-3 h-3 bg-accent rounded-full animate-pulse"></span>
                </button>
            </div>

            <!-- Fenêtre du Chatbot -->
            <div id="chatbotWindow" class="fixed bottom-20 right-6 w-96 h-[500px] bg-white rounded-3xl shadow-2xl z-50 hidden flex flex-col border border-forest/10">
                <!-- Header -->
                <div class="bg-gradient-to-r from-forest to-primary p-4 rounded-t-3xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="bg-white/20 p-2 rounded-xl">
                                <span class="material-symbols-outlined text-white text-xl">smart_toy</span>
                            </div>
                            <div>
                                <h3 class="text-white font-black">Assistant Accueil Immo</h3>
                                <p class="text-white/80 text-xs">En ligne - Répond instantanément</p>
                            </div>
                        </div>
                        <button onclick="chatBot.toggleChatbot()" class="text-white/80 hover:text-white transition-colors">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gradient-to-b from-white to-ivory-white">
                    <!-- Message de bienvenue -->
                    <div class="flex gap-3 chat-bubble">
                        <div class="bg-forest text-white p-3 rounded-2xl rounded-tl-none max-w-[80%]">
                            <p class="text-sm">👋 Bonjour ! Je suis votre assistant virtuel Accueil Immo CI. Comment puis-je vous aider aujourd'hui ?</p>
                        </div>
                    </div>

                    <!-- Options rapides -->
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="chatBot.sendQuickMessage('Je cherche un appartement à louer')" class="bg-primary/10 text-primary p-3 rounded-xl text-xs font-medium hover:bg-primary/20 transition-colors">
                            🏠 Louer un bien
                        </button>
                        <button onclick="chatBot.sendQuickMessage('Je veux vendre ma maison')" class="bg-accent/10 text-accent p-3 rounded-xl text-xs font-medium hover:bg-accent/20 transition-colors">
                            💰 Vendre un bien
                        </button>
                        <button onclick="chatBot.sendQuickMessage('Quels sont vos services ?')" class="bg-forest/10 text-forest p-3 rounded-xl text-xs font-medium hover:bg-forest/20 transition-colors">
                            📋 Nos services
                        </button>
                        <button onclick="chatBot.sendQuickMessage('Comment contacter un agent ?')" class="bg-primary/10 text-primary p-3 rounded-xl text-xs font-medium hover:bg-primary/20 transition-colors">
                            📞 Contacter un agent
                        </button>
                    </div>
                </div>

                <!-- Zone de saisie -->
                <div class="p-4 border-t border-forest/10 bg-white rounded-b-3xl">
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="chatInput" 
                            placeholder="Tapez votre message..." 
                            class="flex-1 px-4 py-3 border border-forest/20 rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all text-sm"
                            onkeypress="chatBot.handleKeyPress(event)"
                        >
                        <button 
                            onclick="chatBot.sendMessage()" 
                            class="bg-primary text-white p-3 rounded-xl hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            id="sendButton"
                        >
                            <span class="material-symbols-outlined">send</span>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Ajouter le HTML au body
        document.body.insertAdjacentHTML('beforeend', chatbotHTML);
    }

    loadStyles() {
        const styles = `
            <style id="chatbot-styles">
                .chat-bubble {
                    animation: slideInUp 0.3s ease-out;
                }
                @keyframes slideInUp {
                    from {
                        opacity: 0;
                        transform: translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                .typing-indicator {
                    display: inline-flex;
                    gap: 4px;
                }
                .typing-dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background-color: var(--vibrant-orange);
                    animation: typing 1.4s infinite;
                }
                .typing-dot:nth-child(2) {
                    animation-delay: 0.2s;
                }
                .typing-dot:nth-child(3) {
                    animation-delay: 0.4s;
                }
                @keyframes typing {
                    0%, 60%, 100% {
                        transform: translateY(0);
                    }
                    30% {
                        transform: translateY(-10px);
                    }
                }
                .animate-bounce-slow {
                    animation: bounce 3s infinite;
                }
            </style>
        `;
        document.head.insertAdjacentHTML('beforeend', styles);
    }

    setupEventListeners() {
        // Écouteur pour la touche Échap
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.toggleChatbot();
            }
        });

        // Écouteur pour cliquer en dehors du chatbot
        document.addEventListener('click', (e) => {
            if (this.isOpen) {
                const chatWindow = document.getElementById('chatbotWindow');
                const chatButton = document.getElementById('chatbotButton');
                
                // Vérifier si le clic est en dehors du chatbot
                if (!chatWindow.contains(e.target) && !chatButton.contains(e.target)) {
                    this.toggleChatbot();
                }
            }
        });
    }

    // Réponses prédéfinies du chatbot
    getBotResponse(message) {
        const lowerMessage = message.toLowerCase();
        
        const responses = {
            'louer': '🏠 **Nos services de location :**\n\n• Appartements, villas, studios\n• Toutes les communes d\'Abidjan\n• Prix adaptés à votre budget\n• Visites gratuites\n\nVoulez-vous voir nos biens disponibles ?',
            
            'vendre': '💰 **Service de vente :**\n\n• Estimation gratuite de votre bien\n• Photos professionnelles\n• Promotion sur nos plateformes\n• Accompagnement juridique\n\nQuel type de bien souhaitez-vous vendre ?',
            
            'services': '📋 **Nos services complets :**\n\n🏠 **Location** - Appartements, villas, bureaux\n💰 **Vente** - Estimation et promotion\n🔑 **Gestion** - Gestion locative complète\n🏨 **Conciergerie** - Services premium\n\nQuel service vous intéresse ?',
            
            'contact': '📞 **Contacter un agent :**\n\n• Téléphone : +225 07 00 00 00 00\n• Email : contact@accueilimmo.ci\n• WhatsApp : +225 07 00 00 00 01\n\nOu laissez votre numéro et on vous rappelle !',
            
            'prix': '💵 **Nos tarifs :**\n\n• Location : 50 000 - 500 000 FCFA/mois\n• Vente : 5M - 100M FCFA\n• Honoraires : 5% du montant\n\nQuel est votre budget ?',
            
            'commune': '📍 **Zones couvertes :**\n\n✅ Cocody, Marcory, Plateau\n✅ Riviera, Bingerville\n✅ Yopougon, Treichville\n✅ Assinie, Grand-Bassam\n\nDans quelle commune cherchez-vous ?',
            
            'default': 'Je comprends votre demande. Voici ce que je peux vous aider :\n\n🏠 **Trouver un bien à louer**\n💰 **Vendre un bien**\n📋 **Découvrir nos services**\n📞 **Contacter un agent**\n\nN\'hésitez pas à me poser des questions précises !'
        };

        // Chercher des mots-clés
        for (const [key, response] of Object.entries(responses)) {
            if (lowerMessage.includes(key)) {
                return response;
            }
        }
        
        return responses.default;
    }

    toggleChatbot() {
        const chatWindow = document.getElementById('chatbotWindow');
        const chatButton = document.getElementById('chatbotButton');
        
        this.isOpen = !this.isOpen;
        
        if (this.isOpen) {
            chatWindow.classList.remove('hidden');
            chatWindow.classList.add('flex');
            chatButton.classList.add('scale-0');
            document.getElementById('chatInput').focus();
        } else {
            chatWindow.classList.add('hidden');
            chatWindow.classList.remove('flex');
            chatButton.classList.remove('scale-0');
        }
    }

    sendMessage() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        
        if (!message || this.isTyping) return;
        
        // Ajouter le message utilisateur
        this.addMessage(message, 'user');
        
        // Vider l'input
        input.value = '';
        
        // Désactiver l'input
        this.disableInput();
        
        // Simuler la réponse du bot
        setTimeout(() => {
            const response = this.getBotResponse(message);
            this.addMessage(response, 'bot');
            this.enableInput();
        }, 1000);
    }

    sendQuickMessage(message) {
        document.getElementById('chatInput').value = message;
        this.sendMessage();
    }

    addMessage(message, sender) {
        const messagesContainer = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex gap-3 chat-bubble ${sender === 'user' ? 'justify-end' : ''}`;
        
        if (sender === 'user') {
            messageDiv.innerHTML = `
                <div class="bg-primary text-white p-3 rounded-2xl rounded-tr-none max-w-[80%]">
                    <p class="text-sm">${message}</p>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="bg-forest text-white p-3 rounded-2xl rounded-tl-none max-w-[80%]">
                    <p class="text-sm whitespace-pre-line">${message}</p>
                </div>
            `;
        }
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Sauvegarder le message
        this.messages.push({ sender, message, timestamp: new Date() });
    }

    showTypingIndicator() {
        const messagesContainer = document.getElementById('chatMessages');
        const typingDiv = document.createElement('div');
        typingDiv.id = 'typingIndicator';
        typingDiv.className = 'flex gap-3';
        typingDiv.innerHTML = `
            <div class="bg-forest/20 p-3 rounded-2xl rounded-tl-none">
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        
        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    hideTypingIndicator() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) {
            indicator.remove();
        }
    }

    disableInput() {
        this.isTyping = true;
        document.getElementById('chatInput').disabled = true;
        document.getElementById('sendButton').disabled = true;
        this.showTypingIndicator();
    }

    enableInput() {
        this.isTyping = false;
        document.getElementById('chatInput').disabled = false;
        document.getElementById('sendButton').disabled = false;
        this.hideTypingIndicator();
        document.getElementById('chatInput').focus();
    }

    handleKeyPress(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage();
        }
    }

    // Méthodes pour l'intégration avec l'API
    async sendToAPI(message) {
        try {
            // Intégration future avec l'API backend
            const response = await fetch('/api/chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message })
            });
            
            const data = await response.json();
            return data.response;
        } catch (error) {
            console.error('Erreur API chatbot:', error);
            return this.getBotResponse(message);
        }
    }

    // Méthodes pour la personnalisation
    setWelcomeMessage(message) {
        const welcomeDiv = document.querySelector('#chatMessages .chat-bubble:first-child p');
        if (welcomeDiv) {
            welcomeDiv.textContent = message;
        }
    }

    setQuickButtons(buttons) {
        const quickButtonsDiv = document.querySelector('#chatMessages .grid');
        if (quickButtonsDiv) {
            quickButtonsDiv.innerHTML = buttons.map(btn => `
                <button onclick="chatBot.sendQuickMessage('${btn.message}')" class="bg-primary/10 text-primary p-3 rounded-xl text-xs font-medium hover:bg-primary/20 transition-colors">
                    ${btn.icon} ${btn.label}
                </button>
            `).join('');
        }
    }

    // Méthodes pour les analytics
    trackMessage(message, sender) {
        // Intégration future avec Google Analytics ou autre
        console.log(`Chatbot message - ${sender}: ${message}`);
    }

    getChatHistory() {
        return this.messages;
    }

    clearChatHistory() {
        this.messages = [];
        const messagesContainer = document.getElementById('chatMessages');
        // Garder seulement le message de bienvenue et les boutons rapides
        const welcomeMessage = messagesContainer.querySelector('.chat-bubble');
        const quickButtons = messagesContainer.querySelector('.grid');
        messagesContainer.innerHTML = '';
        if (welcomeMessage) messagesContainer.appendChild(welcomeMessage);
        if (quickButtons) messagesContainer.appendChild(quickButtons);
    }
}

// Initialiser le chatbot
let chatBot;
document.addEventListener('DOMContentLoaded', function() {
    chatBot = new ChatBot();
    
    // Ajouter un effet d'entrée au bouton
    setTimeout(() => {
        const button = document.getElementById('chatbotButton');
        if (button) {
            button.classList.add('animate-bounce-slow');
        }
    }, 2000);
});

// Exporter pour utilisation globale
window.chatBot = chatBot;
