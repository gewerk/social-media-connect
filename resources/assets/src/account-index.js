/* global socialMediaConnectProviders */

// Add extension
if (typeof Craft.SocialMediaConnect === typeof undefined) {
  Craft.SocialMediaConnect = {};
}

Craft.SocialMediaConnect.AccountIndex = Craft.BaseElementIndex.extend({
  publishableSections: null,
  $newEntryBtnGroup: null,
  $newEntryBtn: null,

  init: function (elementType, $container, settings) {
    this.on('selectSite', this.updateButton.bind(this));
    this.base(elementType, $container, settings);
  },

  updateButton: function () {
    // Remove the old button, if there is one
    if (this.$newEntryBtnGroup) {
      this.$newEntryBtnGroup.remove();
    }

    this.$newEntryBtnGroup = $('<div class="btngroup submit"/>');
    this.$newEntryBtn = $('<button/>', {
      type: 'button',
      class: 'btn submit add icon menubtn',
      text: Craft.t('social-media-connect', 'Add account'),
    }).appendTo(this.$newEntryBtnGroup);

    const $menu = $('<div/>', { class: 'menu' }).appendTo(
      this.$newEntryBtnGroup,
    );
    const $menuList = $('<ul/>', { class: 'padded' }).appendTo($menu);
    const providers = socialMediaConnectProviders || [];

    for (let i = 0; i < providers.length; i++) {
      const provider = providers[i];
      const $li = $('<li/>').appendTo($menuList);

      $('<a/>', {
        href: Craft.getActionUrl('social-media-connect/accounts/connect', {
          provider: provider.handle,
          siteId: this.siteId,
        }),
        html: `<span class="smc-menu-icon" aria-hidden="true">${
          provider.icon
        }</span>${Craft.escapeHtml(provider.name)}`,
      }).appendTo($li);
    }

    new Garnish.MenuBtn(this.$newEntryBtn);
    this.addButton(this.$newEntryBtnGroup);
  },
});

Craft.registerElementIndexClass(
  'Gewerk\\SocialMediaConnect\\Element\\Account',
  Craft.SocialMediaConnect.AccountIndex,
);
