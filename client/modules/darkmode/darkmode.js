({
    currentTheme: '',
    mainHeader: null,
    mediaQuery: null,

    // Module initialization
    init() {
        moduleLoaded("darkmode", this);

        // Load the current theme from local storage
        this.currentTheme = lStore('theme');
        this.mainHeader = document.querySelector('.main-header');

        // Apply the current theme
        this.applyTheme(this.currentTheme);

        // Create the theme selection dropdown menu
        this.createThemeDropdown();

        // Attach event listeners to the theme selection elements
        this.attachEventListeners();
    },

    // Create the theme selection dropdown menu
    createThemeDropdown() {
        $(`
            <li class="nav-item dropdown nav-item-back-hover">
                <span class="nav-link pointer" data-toggle="dropdown">
                    <i class="fas fa-lg fa-fw fa-moon"></i>
                </span>
                <div class="dropdown-menu">
                    <a class="dropdown-item theme-select ${!this.currentTheme || this.currentTheme === 'auto' ? 'active' : ''}" href="#" data-theme="auto">${i18n('darkmode.auto')}</a>
                    <a class="dropdown-item theme-select ${this.currentTheme === 'dark' ? 'active' : ''}" href="#" data-theme="dark">${i18n('darkmode.dark')}</a>
                    <a class="dropdown-item theme-select ${this.currentTheme === 'light' ? 'active' : ''}" href="#" data-theme="light">${i18n('darkmode.light')}</a>
                </div>
            </li>
        `).insertAfter("#rightTopDynamic");
    },

    // Attach event listeners to the theme selection elements
    attachEventListeners() {
        $('.theme-select').on("click", (e) => this.switchTheme(e));
    },

    // Apply the selected theme
    applyTheme(theme) {
        switch (theme) {
            case 'dark':
                this.setDark();
                break;
            case 'light':
                this.setLight();
                break;
            case 'auto':
            default:
                this.setAuto();
                break;
        }
    },

    // Set the dark theme
    setDark(updateStore = true) {
        document.body.classList.add("dark-mode");
        this.updateNavbar('dark');

        if (updateStore) {
            this.currentTheme = 'dark';
            lStore('theme', this.currentTheme);
        }
    },

    // Set the light theme
    setLight(updateStore = true) {
        document.body.classList.remove("dark-mode");
        this.updateNavbar('light');

        if (updateStore) {
            this.currentTheme = 'light';
            lStore('theme', this.currentTheme);
        }
    },

    // Update the theme based on system settings
    updateMedia(media) {
        if (media.matches) {
            this.setDark(false);
        } else {
            this.setLight(false);
        }
    },

    // Automatically apply the theme based on system settings
    setAuto() {
        this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        this.updateMedia(this.mediaQuery);
        this.mediaQuery.onchange = (e) => this.updateMedia(e);

        this.currentTheme = 'auto';
        lStore('theme', this.currentTheme);
    },

    // Update the navbar classes based on the theme
    updateNavbar(theme) {
        if (theme === 'dark') {
            this.mainHeader.classList.add('navbar-dark');
            this.mainHeader.classList.remove('navbar-light');
        } else {
            this.mainHeader.classList.add('navbar-light');
            this.mainHeader.classList.remove('navbar-dark');
        }
    },

    // Switch the theme
    switchTheme(e) {
        e.preventDefault();
        $('.theme-select').removeClass('active');
        $(e.target).addClass('active');
        const theme = $(e.target).data("theme");

        // Disable the media query listener when manually selecting a theme
        if (this.mediaQuery && theme && theme !== 'auto') {
            this.mediaQuery.onchange = null;
        }

        this.applyTheme(theme);
    }
}).init();
