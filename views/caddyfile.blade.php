@foreach($domains as $domain)
{{$domain}}:80 {
    reverse_proxy localhost:{{$publicPort}}
}
@endforeach