// Thème partagé (couleurs et police de la charte Accueil Immo)
tailwind.config = {
    theme: {
        extend: {
            colors: {
                primary: '#FF8A00',
                'primary-dark': '#E07800',
                forest: '#064E3B',
                'forest-light': '#0B6B52',
                accent: '#2BEE79',
                cream: '#FCFAF7',
                sand: '#F5F1EA'
            },
            fontFamily: {
                sans: ['Manrope', 'system-ui', 'sans-serif']
            },
            borderRadius: {
                '4xl': '2rem'
            }
        }
    }
};
