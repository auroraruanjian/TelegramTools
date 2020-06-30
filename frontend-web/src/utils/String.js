String.prototype.replaceTime=function(substr,replacement,time){
    //console.log(this,substr,replacement);
    var _string = this.split(substr);
    _string[time] = _string[time] + '&{{replace}}';
    _string = _string.join(substr);
    return _string.replace('&{{replace}}'+substr,replacement);
}
