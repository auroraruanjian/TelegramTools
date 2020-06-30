import fetch from '@/utils/fetch'

export function getAccounts(){
    return fetch({
        url: 'telegram/index',
        method: 'get',
    });
}

/**
 * 认证用户
 * @param data
 */
export function postAuth( data ) {
    return fetch({
        url: 'telegram/auth',
        method: 'post',
        data,
        transformRequest: [function (data) {
            // Do whatever you want to transform the data
            let ret = ''
            for (let it in data) {
                ret += encodeURIComponent(it) + '=' + encodeURIComponent(data[it]) + '&'
            }
            return ret.slice(0,-1);
        }],
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        timeout:100000
    });
}

/**
 * 获取所有对话
 * @param data
 */
export function getDialogs( data ){
    return fetch({
        url: 'telegram/dialogs',
        method: 'get',
        params:{
            account_id : data.account_id,
            get_phone : data.get_phone
        },
        timeout:100000
    });
}

/**
 * 获取所有对话
 * @param data
 */
export function getMessage( data ){
    return fetch({
        url: 'telegram/messages',
        method: 'post',
        data,
        timeout:100000
    });
}
