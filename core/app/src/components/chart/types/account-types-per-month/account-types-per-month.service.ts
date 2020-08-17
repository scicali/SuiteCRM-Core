import {Injectable} from '@angular/core';
import {Observable} from 'rxjs';
import {ListViewStore, Record} from '@store/list-view/list-view.store';
import {map} from 'rxjs/operators';
import {LineChartDataSource, LineChartResult} from '@components/chart/charts/line-chart/line-chart.model';
import {SystemConfigStore} from '@store/system-config/system-config.store';
import {DateTime} from 'luxon';
import {LanguageStore} from '@store/language/language.store';

@Injectable()
export class AccountTypesPerMonthLineChart implements LineChartDataSource {

    key = 'account_types_per_month';
    chartType = 'line-chart';
    scheme = 'picnic';
    xAxis = true;
    yAxis = true;
    gradient = false;
    legend = true;
    showXAxisLabel = true;
    xAxisLabel = 'Months';
    showYAxisLabel = false;
    yAxisLabel = '';
    xScaleMin = 0;
    xScaleMax = 12;
    xAxisTicks = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    constructor(
        protected listStore: ListViewStore,
        protected configs: SystemConfigStore,
        protected language: LanguageStore
    ) {
    }

    getResults(): Observable<LineChartResult[]> {
        const noTypeLabel = this.language.getAppString('LBL_LINK_NONE');

        return this.listStore.records$.pipe(map((records: Record[]) => {
            const results: LineChartResult[] = [];


            const group: { [key: string]: LineChartResult } = {};

            if (records) {
                records.forEach(record => {
                    if (record.type !== 'Account') {
                        return;
                    }

                    if (!record.attributes.date_entered) {
                        return;
                    }

                    const name = record.attributes.account_type || noTypeLabel;
                    const month = this.getMonth(record.attributes.date_entered);

                    if (!isFinite(month)) {
                        return;
                    }

                    if (!group[name]) {
                        const series = [];
                        for (let i = 1; i <= 12; i++) {
                            series.push(
                                {name: i, value: 0}
                            );
                        }

                        group[name] = {name, series};

                    }

                    group[name].series[month - 1].value++;
                });
            }

            Object.keys(group).forEach(key => {
                results.push(group[key]);
            });

            return results;
        }));
    }

    getMonth(date: string): number {
        const parts = date.split(' ');
        if (!parts || !parts[0]) {
            return null;
        }
        const dateTime = DateTime.fromFormat(parts[0], this.getDateFormat());
        return dateTime.month;
    }

    getDateFormat(): string {

        const dateFormat = this.configs.getConfigValue('date_format');

        if (dateFormat) {
            return dateFormat;
        }

        return 'yyyy-MM-dd';
    }
}
