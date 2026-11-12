const fs = require('fs');
const https = require('https');
const crypto = require('crypto');

const access_token = '322936015478592488';
const cacheFile = './.migu_lnss_cache.json';
const TTL = 900;

const channels = {
    '1': {'name': 'CCTV-1综合', 'pid': '608807420', 'group': '央视'},
    '2': {'name': 'CCTV-2财经', 'pid': '631780532', 'group': '央视'},
    '3': {'name': 'CCTV-3综艺', 'pid': '624878271', 'group': '央视'},
    '4': {'name': 'CCTV-4中文国际', 'pid': '631780421', 'group': '央视'},
    '5': {'name': 'CCTV-5体育', 'pid': '641886683', 'group': '央视'},
    '6': {'name': 'CCTV-5+体育赛事', 'pid': '641886773', 'group': '央视'},
    '7': {'name': 'CCTV-6电影', 'pid': '624878396', 'group': '央视'},
    '8': {'name': 'CCTV-7国防军事', 'pid': '673168121', 'group': '央视'},
    '9': {'name': 'CCTV-8电视剧', 'pid': '624878356', 'group': '央视'},
    '10': {'name': 'CCTV-9纪录', 'pid': '673168140', 'group': '央视'},
    '11': {'name': 'CCTV-10科教', 'pid': '624878405', 'group': '央视'},
    '12': {'name': 'CCTV-11戏曲', 'pid': '667987558', 'group': '央视'},
    '13': {'name': 'CCTV-12社会与法', 'pid': '673168185', 'group': '央视'},
    '14': {'name': 'CCTV-13新闻', 'pid': '608807423', 'group': '央视'},
    '15': {'name': 'CCTV-14少儿', 'pid': '624878440', 'group': '央视'},
    '16': {'name': 'CCTV-15音乐', 'pid': '673168223', 'group': '央视'},
    '17': {'name': 'CCTV-17农业农村', 'pid': '673168256', 'group': '央视'},
    '18': {'name': 'CCTV发现之旅', 'pid': '624878970', 'group': '央视'},
    '19': {'name': 'CCTV老故事', 'pid': '884121956', 'group': '央视'},
    '20': {'name': 'CCTV中学生', 'pid': '708869532', 'group': '央视'},
    '21': {'name': 'CGTN纪录', 'pid': '609006487', 'group': '央视'},
    '22': {'name': 'CCTV西班牙语', 'pid': '609006450', 'group': '央视'},
    '23': {'name': 'CCTV法语', 'pid': '609006476', 'group': '央视'},
    '24': {'name': 'CCTV阿拉伯语', 'pid': '609154345', 'group': '央视'},
    '25': {'name': 'CCTV俄语', 'pid': '609006446', 'group': '央视'},
    '26': {'name': 'CHC影迷电影', 'pid': '952383261', 'group': '央视'},
    '27': {'name': '东方卫视', 'pid': '651632648', 'group': '卫视'},
    '28': {'name': '江苏卫视', 'pid': '623899368', 'group': '卫视'},
    '29': {'name': '广东卫视', 'pid': '608831231', 'group': '卫视'},
    '30': {'name': '北京卫视', 'pid': '630287636', 'group': '卫视'},
    '31': {'name': '辽宁卫视', 'pid': '630291707', 'group': '卫视'},
    '32': {'name': '河北卫视', 'pid': '962042070', 'group': '卫视'},
    '33': {'name': '江西卫视', 'pid': '783847495', 'group': '卫视'},
    '34': {'name': '河南卫视', 'pid': '790187291', 'group': '卫视'},
    '35': {'name': '陕西卫视', 'pid': '738910838', 'group': '卫视'},
    '36': {'name': '大湾区卫视', 'pid': '608917627', 'group': '卫视'},
    '37': {'name': '湖北卫视', 'pid': '947472496', 'group': '卫视'},
    '38': {'name': '吉林卫视', 'pid': '947472500', 'group': '卫视'},
    '39': {'name': '青海卫视', 'pid': '947472506', 'group': '卫视'},
    '40': {'name': '东南卫视', 'pid': '849116810', 'group': '卫视'},
    '41': {'name': '海南卫视', 'pid': '947472502', 'group': '卫视'},
    '42': {'name': '海峡卫视', 'pid': '849119120', 'group': '卫视'},
    '43': {'name': '中国农林卫视', 'pid': '956904896', 'group': '卫视'},
    '44': {'name': '兵团卫视', 'pid': '956923145', 'group': '卫视'},
    '45': {'name': '宁夏卫视', 'pid': '738910535', 'group': '卫视'},
    '46': {'name': '三沙卫视', 'pid': '961023778', 'group': '卫视'},
    '47': {'name': '黑龙江卫视', 'pid': '738906901', 'group': '卫视'},
    '48': {'name': '上海新闻综合', 'pid': '651632657', 'group': '地方'},
    '49': {'name': '上海第一财经', 'pid': '608780988', 'group': '地方'},
    '50': {'name': '南京新闻综合频道', 'pid': '838109047', 'group': '地方'},
    '51': {'name': '南京教科频道', 'pid': '838153729', 'group': '地方'},
    '52': {'name': '南京十八频道', 'pid': '838151753', 'group': '地方'},
    '53': {'name': '江苏城市频道', 'pid': '626064714', 'group': '地方'},
    '54': {'name': '江苏国际', 'pid': '626064674', 'group': '地方'},
    '55': {'name': '江苏教育', 'pid': '628008321', 'group': '地方'},
    '56': {'name': '江苏影视频道', 'pid': '626064697', 'group': '地方'},
    '57': {'name': '江苏综艺频道', 'pid': '626065193', 'group': '地方'},
    '58': {'name': '公共新闻频道', 'pid': '626064693', 'group': '地方'},
    '59': {'name': '优漫卡通频道', 'pid': '626064703', 'group': '地方'},
    '60': {'name': '盐城新闻综合', 'pid': '639731825', 'group': '地方'},
    '61': {'name': '淮安新闻综合', 'pid': '639731826', 'group': '地方'},
    '62': {'name': '泰州新闻综合', 'pid': '639731818', 'group': '地方'},
    '63': {'name': '连云港新闻综合', 'pid': '639731715', 'group': '地方'},
    '64': {'name': '宿迁新闻综合', 'pid': '639731832', 'group': '地方'},
    '65': {'name': '徐州新闻综合', 'pid': '639731747', 'group': '地方'},
    '66': {'name': '南通新闻综合', 'pid': '955227985', 'group': '地方'},
    '67': {'name': '宜兴新闻综合', 'pid': '955227996', 'group': '地方'},
    '68': {'name': '溧水新闻综合', 'pid': '639737327', 'group': '地方'},
    '69': {'name': '镇江新闻综合', 'pid': '639731783', 'group': '地方'},
    '70': {'name': '陕西银龄频道', 'pid': '956909362', 'group': '地方'},
    '71': {'name': '陕西都市青春频道', 'pid': '956909358', 'group': '地方'},
    '72': {'name': '陕西体育休闲频道', 'pid': '956909356', 'group': '地方'},
    '73': {'name': '陕西秦腔频道', 'pid': '956909303', 'group': '地方'},
    '74': {'name': '陕西新闻资讯频道', 'pid': '956909289', 'group': '地方'},
    '75': {'name': '财富天下', 'pid': '956923159', 'group': '地方'},
    '76': {'name': '广东珠江', 'pid': '639731952', 'group': '地方'},
    '77': {'name': '广东影视', 'pid': '614961829', 'group': '地方'},
    '78': {'name': '嘉佳卡通', 'pid': '614952364', 'group': '地方'},
    '79': {'name': '红色轮播台', 'pid': '713600957', 'group': '轮播'},
    '80': {'name': '新片放映厅', 'pid': '619495952', 'group': '轮播'},
    '81': {'name': '经典香港电影', 'pid': '625703337', 'group': '轮播'},
    '82': {'name': '军事迷必看大片', 'pid': '617432318', 'group': '轮播'},
    '83': {'name': '咪咕24小时体育台', 'pid': '654102378', 'group': '轮播'},
    '84': {'name': '新动力量创一流', 'pid': '713589837', 'group': '轮播'},
};

// http 请求封装
function fetchUrl(url, headers = [], follow = true, timeout = 15000) {
    return new Promise((resolve, reject) => {
        const h = {};
        headers.forEach(x => {
            const [k, v] = x.split(': ');
            h[k] = v;
        })
        const req = https.get(url, { headers: h, timeout, rejectUnauthorized: false }, res => {
            let buf = [];
            res.on('data', d => buf.push(d));
            res.on('end', () => {
                const body = Buffer.concat(buf).toString('utf-8');
                const ret = {
                    code: res.statusCode,
                    body,
                    headers: res.headers,
                    location: res.headers.location || ''
                }
                if (follow && [301,302,307].includes(res.statusCode) && ret.location) {
                    fetchUrl(ret.location, headers, follow, timeout).then(resolve).catch(reject);
                } else {
                    resolve(ret);
                }
            })
        })
        req.on('error', e => reject(e));
        req.end();
    })
}

// md5
function md5(str) {
    return crypto.createHash('md5').update(str).digest('hex');
}

// 生成咪咕API地址
function miguApiUrl(pid) {
    const t = String(Math.floor(Date.now()));
    const v = '2600034600';
    const cid = md5(String(Math.floor(Date.now())));
    const h = [
        `AppVersion: ${v}`,
        'TerminalId: android',
        `X-UP-CLIENT-CHANNEL-ID: ${v}-99000-201600010010028`,
        `ClientId: ${cid}`
    ];
    if(pid !== '641886683' && pid !== '641886773') h.push('appCode: miguvideo_default_android');
    const salt = String(Math.floor(Math.random()*1000000)).padStart(6,'0') + '25';
    const sign = md5( md5(t + pid + v.substring(0,8)) + '2cac4f2c6c3346a5b34e085725ef7e33migu' + salt.substring(0,4) )
    const u = `https://play.miguvideo.com/playurl/v1/play/playurl?sign=${sign}&rateType=3&contId=${pid}&timestamp=${t}&salt=${salt}&flvEnable=true&super4k=true&h265N=true&4kvivid=true&2Kvivid=true&vivid=2`
    return [u, h];
}

// ddCalcu 算法
function ddCalcu(puData, pid) {
    const keys = 'cdabyzwxkl';
    let dd = '';
    const n = puData.length;
    for(let i=0; i < n/2; i++){
        dd += puData[n - i -1] + puData[i];
        if(i === 1) dd += 'v';
        if(i === 2) dd += keys[ new Date().getFullYear().toString().substring(2,3) ];
        if(i === 3) dd += keys[ pid.substring(6,7) ];
        if(i === 4) dd += 'a';
    }
    return dd;
}

// 解析最终播放地址
async function miguResolve(pid) {
    const [u, h] = miguApiUrl(pid);
    const res = await fetchUrl(u, h, false);
    let j;
    try{ j = JSON.parse(res.body); }catch(e){ return null; }
    if(!j?.body?.urlInfo?.url) return null;
    let url = j.body.urlInfo.url;
    const m = url.match(/&puData=([^&]+)/);
    if(!m) return null;
    url += `&ddCalcu=${ddCalcu(m[1], pid)}&sv=10004&ct=android`;
    for(let i=0; i<6; i++){
        const r = await fetchUrl(url, ['User-Agent: MiguVideo/6.2.30'], false);
        if(r.code !== 302) return url;
        if(!r.location || r.location.startsWith('http://bofang')) return url;
        url = r.location;
    }
    return url;
}

// 校验是否是m3u8
function m3u8Body(body) {
    if(!body) return null;
    const t = body.replace(/^\ufeff/,'').trimStart();
    return t.startsWith('#EXTM3U') ? t : null;
}

// 相对路径转绝对地址
function resolveUrl(base, relative) {
    if(/^https?:\/\//i.test(relative)) return relative;
    const p = new URL(base);
    if(relative[0] === '/') return p.origin + relative;
    const basePath = p.pathname.replace(/[^/]*$/, '');
    return new URL(basePath + relative, p.origin).href;
}

// 重写m3u8分片为绝对地址
function rewriteAbsolute(body, baseUrl) {
    let out = '';
    for(const line of body.split('\n')){
        const l = line.trim();
        if(l === '') continue;
        if(l.startsWith('#')){
            const mk = l.match(/URI="([^"]+)"/);
            if(mk) out += l.replace(mk[1], resolveUrl(baseUrl, mk[1])) + '\n';
            else out += l + '\n';
        }else{
            out += resolveUrl(baseUrl, l) + '\n';
        }
    }
    return out;
}

// 缓存读写
function loadCache() {
    try{
        const raw = fs.readFileSync(cacheFile, 'utf8');
        return JSON.parse(raw);
    }catch(e){ return {}; }
}
function saveCache(cache) {
    fs.writeFileSync(cacheFile, JSON.stringify(cache, null, 2));
}

// 主入口
async function main() {
    const cache = loadCache();
    let m3u = '#EXTM3U\n';
    const ids = Object.keys(channels);
    for(const cid of ids) {
        const ch = channels[cid];
        console.log(`正在解析：${ch.name} pid:${ch.pid}`);
        let playUrl = null;
        // 读取缓存
        const item = cache[ch.pid];
        if(item && (Date.now()/1000 - item.t) < TTL) {
            const r = await fetchUrl(item.u);
            if(m3u8Body(r.body)) {
                playUrl = item.u;
            }
        }
        // 缓存失效，重新解析
        if(!playUrl) {
            try {
                playUrl = await miguResolve(ch.pid);
                if(!playUrl) throw new Error('解析失败');
                const r = await fetchUrl(playUrl);
                let m8 = m3u8Body(r.body);
                if(!m8) throw new Error('返回不是m3u8');
                // 自动选最高码率
                if(/#EXT-X-STREAM-INF/i.test(m8)) {
                    let bestBw = 0, bestUrl = '';
                    let curBw = 0;
                    for(const line of m8.split('\n')) {
                        const mbw = line.match(/BANDWIDTH=(\d+)/);
                        if(mbw) curBw = Number(mbw[1]);
                        else if(line.trim() && !line.startsWith('#')) {
                            if(curBw >= bestBw) {
                                bestBw = curBw;
                                bestUrl = resolveUrl(playUrl, line.trim());
                            }
                            curBw = 0;
                        }
                    }
                    if(bestUrl) {
                        const r2 = await fetchUrl(bestUrl);
                        m8 = m3u8Body(r2.body);
                        playUrl = bestUrl;
                    }
                }
                cache[ch.pid] = { u: playUrl, t: Math.floor(Date.now()/1000) };
                saveCache(cache);
            }catch(err) {
                console.error(`❌ ${ch.name} 解析失败：`, err.message);
                continue;
            }
        }
        m3u += `#EXTINF:-1 tvg-id="${ch.name}", group-title="${ch.group}",${ch.name}\n${playUrl}\n`;
    }
    fs.writeFileSync('./migu.m3u', m3u, 'utf8');
    console.log('✅ 清单生成完成：migu.m3u');
}

main().catch(e=>{console.error(e);process.exit(1);})
