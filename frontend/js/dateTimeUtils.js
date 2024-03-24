import {DateTime} from 'luxon';

const defaultTz = 'America/New_York';

// Date/Time Utilities

export function nowToDateTime(tz = defaultTz) {
    return DateTime.local({zone: tz});
}

export function timestampToDateTime(value, tz = defaultTz) {
    return DateTime.fromSeconds(Number(value), {zone: tz});
}

export function dateTimeToDateTimeString(value) {
    return value.toLocaleString(DateTime.DATETIME_MED);
}

export function dateTimeToTimeString(value) {
    return value.toLocaleString(DateTime.TIME_SIMPLE);
}

export function dateTimeToRelative(value) {
    return value.toRelative();
}
