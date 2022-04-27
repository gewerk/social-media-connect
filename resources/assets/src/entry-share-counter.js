import './entry-share-counter.scss';

// Add extension
if (typeof Craft.SocialMediaConnect === typeof undefined) {
  Craft.SocialMediaConnect = {};
}

// Add interface to our global variable
Craft.SocialMediaConnect.EntryShareCounter = Garnish.Base.extend({
  init(id, settings) {
    this.id = id;
    this.settings = settings;
    this.$container = document.getElementById(id);
    this.$counter = this.$container.querySelector(
      '.smc-entry-share-counter__value-counter',
    );

    const $button = this.$container.querySelector(
      '.smc-entry-share-counter__value-btn',
    );

    $button.addEventListener('click', (event) => {
      event.preventDefault();

      const $shares = document.createElement('div');
      this.loadShares($shares);

      new Garnish.HUD($button, $shares, {
        hudClass: 'hud',
      });
    });

    document.addEventListener('social-media-connect:new-share', () => {
      Craft.postActionRequest(
        'social-media-connect/entry-share-counter/share-counter',
        {
          entryId: this.settings.entryId,
          siteId: this.settings.siteId,
        },
        (response) => {
          this.$counter.textContent = response.count;
        },
      );
    });
  },

  loadShares($target) {
    Craft.postActionRequest(
      'social-media-connect/entry-share-counter/list-shares',
      {
        entryId: this.settings.entryId,
        siteId: this.settings.siteId,
      },
      (response) => {
        this.insertShares($target, response);
      },
    );
  },

  insertShares($target, response) {
    $target.innerHTML = response.shares;
    $target.querySelectorAll('[data-delete-share-id]').forEach(($trigger) => {
      $trigger.addEventListener('click', (event) => {
        event.preventDefault();

        Craft.postActionRequest(
          'social-media-connect/entry-share-counter/delete-share',
          { shareId: $trigger.dataset.deleteShareId },
          (deleteResponse) => {
            if (deleteResponse.success) {
              Craft.cp.displayNotice(
                Craft.t('social-media-connect', 'Share was deleted'),
              );
            } else {
              Craft.cp.displayError(
                Craft.t('social-media-connect', 'Share was not deleted'),
              );
            }

            this.insertShares($target, deleteResponse);
            this.$counter.textContent = deleteResponse.count;
          },
        );
      });
    });
  },
});
