import './compose-share.scss';

// Add extension
if (typeof Craft.SocialMediaConnect === typeof undefined) {
  Craft.SocialMediaConnect = {};
}

// Add input field to Craft global
Craft.SocialMediaConnect.ComposeShare = Garnish.Base.extend({
  init(id, settings) {
    this.id = id;
    this.settings = settings;
    this.currentAccount = this.settings.accounts[0] || null;
    this.params = {
      entryId: this.settings.entryId,
      siteId: this.settings.siteId,
    };

    if (!this.currentAccount) {
      return;
    }

    this.insertShareToSocialButton();
  },

  insertShareToSocialButton() {
    const $button = document.createElement('button');

    $button.type = 'button';
    $button.className = 'btn';
    $button.textContent = Craft.t(
      'social-media-connect',
      'Post to Social Media',
    );

    $button.addEventListener('click', () => this.showComposer());

    const $actionButtons = document.getElementById('action-buttons');
    $actionButtons.insertBefore($button, $actionButtons.firstElementChild);
  },

  showComposer() {
    this.$body = document.createElement('div');
    this.$body.className = 'smc-compose-share__body body';

    this.$submitButton = document.createElement('button');
    this.$submitButton.type = 'submit';
    this.$submitButton.className = 'btn submit smc-compose-share__submit';
    this.$submitButton.textContent = Craft.t(
      'social-media-connect',
      'Post to {account}',
      {
        account: this.currentAccount.name,
      },
    );

    const $accountSwitcher = this.accountSwitcher();

    const $cancelButton = document.createElement('button');
    $cancelButton.type = 'button';
    $cancelButton.className = 'btn';
    $cancelButton.textContent = Craft.t('social-media-connect', 'Cancel');

    const $buttons = document.createElement('div');
    $buttons.className = 'buttons smc-compose-share__buttons';
    $buttons.appendChild($cancelButton);
    $buttons.appendChild(this.$submitButton);

    const $footer = document.createElement('footer');
    $footer.className = 'footer smc-compose-share__footer';
    $footer.appendChild($buttons);

    const $form = document.createElement('form');
    $form.className = 'smc-compose-share__form';
    $form.appendChild($accountSwitcher);
    $form.appendChild(this.$body);
    $form.appendChild($footer);

    const $modal = document.createElement('form');
    $modal.className = 'modal smc-compose-share';
    $modal.appendChild($form);

    this.switchAccounts(this.currentAccount);

    const modal = new Garnish.Modal($modal, {
      hideOnEsc: true,
      hideOnShadeClick: false,
      onHide() {
        modal.destroy();
        $modal.remove();
      },
    });

    $form.addEventListener('submit', (event) => {
      event.preventDefault();

      this.$submitButton.disabled = true;

      const postData = Garnish.getPostData($form);
      const params = Craft.expandPostArray(postData);

      Craft.postActionRequest(
        'social-media-connect/compose/post-share',
        { ...this.params, accountId: this.currentAccount.id, ...params },
        (response) => {
          if (response.success) {
            modal.hide();

            Craft.cp.displayNotice(
              Craft.t(
                'social-media-connect',
                'Successful posted to {account}',
                {
                  account: this.currentAccount.name,
                },
              ),
            );
          } else {
            this.$body.innerHTML = response.fields;

            if (response.error) {
              const $error = document.createElement('div');
              $error.className = 'error';
              $error.textContent = response.error;

              this.$body.insertBefore($error, this.$body.firstElementChild);
            }

            Craft.initUiElements(this.$body);
            this.$submitButton.disabled = false;
          }
        },
      );
    });

    $cancelButton.addEventListener('click', (event) => {
      event.preventDefault();
      modal.hide();
    });
  },

  switchAccounts(account) {
    this.currentAccount = account;
    this.$submitButton.disabled = true;
    this.$submitButton.textContent = Craft.t(
      'social-media-connect',
      'Post to {account}',
      {
        account: account.name,
      },
    );

    Craft.postActionRequest(
      'social-media-connect/compose/fields',
      { ...this.params, accountId: account.id },
      (response) => {
        this.$body.innerHTML = response.fields;
        Craft.initUiElements(this.$body);
        this.$submitButton.disabled = false;
      },
    );
  },

  accountSwitcher() {
    const $accountSwitcher = document.createElement('button');
    const $accountSwitcherIcon = document.createElement('span');

    $accountSwitcherIcon.className = 'smc-menu-icon';
    $accountSwitcherIcon.ariaHidden = 'true';
    $accountSwitcherIcon.innerHTML = this.currentAccount.icon;

    $accountSwitcher.type = 'button';
    $accountSwitcher.id = `${this.id}-account-switcher`;
    $accountSwitcher.className = 'btn menubtn';
    $accountSwitcher.textContent = this.currentAccount.name;
    $accountSwitcher.title = Craft.t(
      'social-media-connect',
      'Post to {account}',
      {
        account: this.currentAccount.name,
      },
    );

    $accountSwitcher.insertBefore(
      $accountSwitcherIcon,
      $accountSwitcher.firstChild,
    );

    const $accountsList = document.createElement('ul');
    this.settings.accounts.forEach((account) => {
      const $li = document.createElement('li');
      const $a = document.createElement('a');
      const $icon = document.createElement('span');

      $icon.className = 'smc-menu-icon';
      $icon.ariaHidden = 'true';
      $icon.innerHTML = account.icon;

      $a.dataset.accountId = account.id;
      $a.textContent = account.name;
      $a.title = Craft.t('social-media-connect', 'Use {account}', {
        account: account.name,
      });

      $a.insertBefore($icon, $a.firstChild);
      $li.appendChild($a);
      $accountsList.appendChild($li);
    });

    const $accounts = document.createElement('div');
    $accounts.className = 'menu';
    $accounts.appendChild($accountsList);

    const $wrap = document.createElement('div');
    $wrap.className = 'smc-compose-share__account-switcher';
    $wrap.appendChild($accountSwitcher);
    $wrap.appendChild($accounts);

    const menu = new Garnish.CustomSelect($accounts);
    const menuBtn = new Garnish.MenuBtn($accountSwitcher, menu);

    menu.on('optionselect', (event) => {
      const { selectedOption: $selectedOption } = event;
      const account = this.settings.accounts.find(
        (account) => account.id == $selectedOption.dataset.accountId,
      );

      $accountSwitcher.textContent = account.name;
      $accountSwitcher.title = Craft.t(
        'social-media-connect',
        'Post to {account}',
        { account: account.name },
      );

      const $icon = $selectedOption.querySelector('.smc-menu-icon');
      if ($icon) {
        $accountSwitcher.insertBefore(
          $icon.cloneNode(true),
          $accountSwitcher.firstChild,
        );
      }

      this.switchAccounts(account);
    });

    this.on('destory', () => {
      menuBtn.destroy();
    });

    return $wrap;
  },
});
