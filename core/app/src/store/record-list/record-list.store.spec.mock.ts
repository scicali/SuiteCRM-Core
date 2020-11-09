import {RecordListStoreFactory} from '@store/record-list/record-list.store.factory';
import {ListGQL} from '@store/record-list/graphql/api.list.get';
import {appStateStoreMock} from '@store/app-state/app-state.store.spec.mock';
import {languageStoreMock} from '@store/language/language.store.spec.mock';
import {messageServiceMock} from '@services/message/message.service.spec.mock';
import {systemConfigStoreMock} from '@store/system-config/system-config.store.spec.mock';
import {userPreferenceStoreMock} from '@store/user-preference/user-preference.store.spec.mock';
import {Observable, of} from 'rxjs';
import {deepClone} from '@base/app-common/utils/object-utils';

/* eslint-disable camelcase, @typescript-eslint/camelcase */
export const recordListMockData = {
    recordList: {
        id: '/docroot/api/records/accounts',
        meta: {
            offsets: {
                current: 10,
                next: 20,
                prev: 0,
                end: 80,
                total: '83',
                totalCounted: true
            },
            ordering: {
                orderBy: 'date_entered',
                sortOrder: 'ASC'
            }
        },
        records: [{
            id: '29319818-dc26-f57d-03e1-5ed77dedd691',
            relationships: [],
            type: 'Account',
            attributes: {
                account_type: '',
                annual_revenue: '',
                aos_contracts: '',
                aos_invoices: '',
                aos_quotes: '',
                assigned_user_id: '',
                assigned_user_link: '',
                assigned_user_name: '',
                billing_address_city: '',
                billing_address_country: '',
                billing_address_postalcode: '',
                billing_address_state: '',
                billing_address_street: '',
                billing_address_street_2: '',
                billing_address_street_3: '',
                billing_address_street_4: '',
                bugs: '',
                calls: '',
                campaign_accounts: '',
                campaign_id: '',
                campaign_name: '',
                campaigns: '',
                cases: '',
                city: '',
                contacts: '',
                created_by: '1',
                created_by_link: '',
                created_by_name: 'admin',
                date_entered: '06/03/2020 10:37',
                date_modified: '06/03/2020 10:37',
                deleted: '0',
                description: '',
                documents: '',
                email: '',
                email1: '',
                email_addresses: '',
                email_addresses_non_primary: '',
                email_addresses_primary: '',
                email_opt_out: '',
                emails: '',
                employees: '',
                encoded_name: 'V8 Api test Account',
                industry: '',
                invalid_email: '',
                jjwg_maps_address_c: '',
                jjwg_maps_geocode_status_c: '',
                jjwg_maps_lat_c: '0.00000000',
                jjwg_maps_lng_c: '0.00000000',
                leads: '',
                meetings: '',
                member_of: '',
                members: '',
                modified_by_name: 'admin',
                modified_user_id: '1',
                modified_user_link: '',
                name: 'V8 Api test Account',
                notes: '',
                opportunities: '',
                ownership: '',
                parent_id: '',
                parent_name: '',
                phone_alternate: '',
                phone_fax: '',
                phone_office: '',
                project: '',
                prospect_lists: '',
                rating: '',
                securitygroups: '',
                shipping_address_city: '',
                shipping_address_country: '',
                shipping_address_postalcode: '',
                shipping_address_state: '',
                shipping_address_street: '',
                shipping_address_street_2: '',
                shipping_address_street_3: '',
                shipping_address_street_4: '',
                sic_code: '',
                tasks: '',
                ticker_symbol: '',
                website: '',
            }
        }]
    }
};

/* eslint-enable camelcase, @typescript-eslint/camelcase */

class ListGQLSpy extends ListGQL {

    constructor() {
        super(null);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    public fetch(
        module: string,
        limit: number,
        offset: number,
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        criteria: { [key: string]: any },
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        sort: { [key: string]: any },
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        metadata: { fields: string[] }
    ): Observable<any> {

        const data = {
            data: {
                getRecordList: deepClone(recordListMockData.recordList)
            }
        };

        data.data.getRecordList.meta.offsets = {
            current: offset,
            next: (offset + limit) || 0,
            prev: (offset - limit) || 0,
            total: 200,
            end: 180
        };

        return of(data);
    }
}

export const listStoreFactoryMock = new RecordListStoreFactory(
    new ListGQLSpy(),
    systemConfigStoreMock,
    userPreferenceStoreMock,
    appStateStoreMock,
    languageStoreMock,
    messageServiceMock,
);
