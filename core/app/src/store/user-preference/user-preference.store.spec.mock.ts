import {BehaviorSubject, Observable, of} from 'rxjs';
import {distinctUntilChanged, shareReplay, take} from 'rxjs/operators';
import {CollectionGQL} from '@services/api/graphql-api/api.collection.get';
import {UserPreferenceStore} from '@store/user-preference/user-preference.store';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const userPreferenceMockData = {
    userPreferences: {
        global: {
            id: '/api/user-preferences/global',
            _id: 'global',
            value: null,
            items: {
                calendar_publish_key: 'cc360e7b-c31c-5a12-4716-5e692263f2c8',
                swap_shortcuts: '',
                navigation_paradigm: 'gm',
                sort_modules_by_name: '',
                subpanel_tabs: '',
                count_collapsed_subpanels: '',
                module_favicon: '',
                no_opps: 'off',
                timezone: 'UTC',
                ut: '1',
                mail_smtpserver: '',
                mail_smtpport: '25',
                mail_smtpuser: '',
                mail_smtppass: '',
                use_real_names: 'off',
                mail_smtpauth_req: '',
                mail_smtpssl: 0,
                email_show_counts: 0,
                user_theme: 'SuiteP',
                editor_type: 'mozaik',
                reminder_time: '60',
                email_reminder_time: '60',
                reminder_checked: '0',
                email_reminder_checked: '0',
                currency: {id: '1', name: 'US Dollar', symbol: '$', iso4217: 'USD'},
                default_currency_significant_digits: '2',
                num_grp_sep: ',',
                dec_sep: '.',
                fdow: '0',
                datef: 'm/d/Y',
                timef: 'H:i',
                default_locale_name_format: 's f l',
                export_delimiter: ',',
                default_export_charset: 'UTF-8',
                email_link_type: 'sugar',
                subtheme: 'Dawn',
                list_max_entries_per_page: 20,
                date_format: 'dd.MM.yyyy',
                time_format: 'HH.mm.ss',
            }
        }
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class UserPreferenceRecordGQLSpy extends CollectionGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetchAll(module: string, metadata: { fields: string[] }): Observable<any> {
        const data = {
            data: {
                userPreferences: {
                    edges: []
                }
            }
        };

        Object.keys(userPreferenceMockData.userPreferences).forEach(key => {
            data.data.userPreferences.edges.push({
                node: userPreferenceMockData.userPreferences[key]
            });
        });

        return of(data).pipe(shareReplay());
    }
}

export const userPreferenceStoreMock = new UserPreferenceStore(new UserPreferenceRecordGQLSpy());
userPreferenceStoreMock.load().pipe(take(1)).subscribe();

export class UserPreferenceMockStore extends UserPreferenceStore {

    constructor(
        protected mockData: BehaviorSubject<any>
    ) {
        super(new UserPreferenceRecordGQLSpy());

        this.userPreferences$ = this.mockData.asObservable().pipe(distinctUntilChanged());
    }

    getUserPreference(key: string): any {

        if (!this.mockData.value || !this.mockData.value[key]) {
            return null;
        }

        return this.mockData.value[key];
    }

}
