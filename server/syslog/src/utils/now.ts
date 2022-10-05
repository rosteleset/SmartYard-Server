const thisMoment = (pg?: any) => {
    let now: Date | string = new Date();
    now = now.getFullYear().toString().padStart(4, '0') + '-' + (now.getMonth() + 1).toString().padStart(2, '0') + '-' + now.getDate().toString().padStart(2, '0') + ' ' + now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0') + ':' + now.getSeconds().toString().padStart(2, '0');
    if (pg) now += '.000';
    return now;
}
export { thisMoment }