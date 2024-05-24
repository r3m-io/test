{{R3M}}
{{$response = Package.R3m.Io.Test:Main:run.test(flags(), options())}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

