'use strict';

define(
    [
        'underscore',
        'pim/controller/base',
        'pim/form-builder',
        'pim/fetcher-registry',
        'pim/user-context',
        'pim/dialog',
        'pim/page-title',
        'pim/error',
        'pim/i18n',
        'module'
    ],
    function (_, BaseController, FormBuilder, FetcherRegistry, UserContext, Dialog, PageTitle, Error, i18n, module) {
        return BaseController.extend({
            initialize: function () {
                this.config = module.config();
            },

            /**
             * {@inheritdoc}
             */
            renderRoute: function (route) {
                return FetcherRegistry.getFetcher(this.config.fetcher).fetch(route.params.code, {cached: false})
                    .then(function (group) {
                        if (!this.active) {
                            return;
                        }

                        var label = _.escape(
                            i18n.getLabel(
                                group.labels,
                                UserContext.get('catalogLocale'),
                                group.code
                            )
                        );

                        PageTitle.set({'group.label': label });

                        FormBuilder.build(group.meta.form)
                            .then(function (form) {
                                this.on('pim:controller:can-leave', function (event) {
                                    form.trigger('pim_enrich:form:can-leave', event);
                                });
                                form.setData(group);
                                form.trigger('pim_enrich:form:entity:post_fetch', group);
                                form.setElement(this.$el).render();
                            }.bind(this));
                    }.bind(this))
                .fail(function (response) {
                    var errorView = new Error(response.responseJSON.message, response.status);
                    errorView.setElement(this.$el).render();
                });
            }
        });
    }
);
