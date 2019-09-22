#![feature(proc_macro_hygiene, decl_macro)]

#[macro_use]
extern crate rocket;

use std::io::Seek;
use inferno::collapse::Collapse;

#[post("/upload", data = "<data>")]
fn upload(data: rocket::Data) -> std::io::Result<String> {
    let xd_opts = inferno::collapse::xdebug::Options::default();
    let mut fg_opts = inferno::flamegraph::Options::default();

    let mut buff = std::io::Cursor::new(Vec::new());
    let mut buff2 = std::io::Cursor::new(Vec::new());
    let mut buff3 = std::io::Cursor::new(Vec::new());

    data.stream_to(&mut buff)?;

    buff.seek(std::io::SeekFrom::Start(0))?;
    inferno::collapse::xdebug::Folder::from(xd_opts).collapse(buff, &mut buff2)?;

    buff2.seek(std::io::SeekFrom::Start(0))?;
    let _ = inferno::flamegraph::from_reader(&mut fg_opts, buff2, &mut buff3);

    let inner = buff3.into_inner();

    Ok(std::str::from_utf8(&inner).unwrap_or_else(|_| "error").to_owned())
}

fn main() {
    rocket::ignite()
        .mount("/", routes![upload]).launch();
}
