(cors) {
    @origin{args.0} header Origin {args.0}
    header @origin{args.0} Access-Control-Allow-Origin "{args.0}"
    header @origin{args.0} Access-Control-Allow-Headers "content-type, x-requested-with"
    header @origin{args.0} Vary Origin
}


@foreach($domains as $domain)
{{$domain}}:80 {
    reverse_proxy localhost:{{$publicPort}}
    header Access-Control-Allow-Methods "POST, GET, OPTIONS"
    @options {
        method OPTIONS
    }
    respond @options 204
    import cors http://localhost:3000
}
@endforeach