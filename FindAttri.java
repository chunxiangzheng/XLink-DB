/**
 * 
 * @author Bruce Lab, Chunxiang Zheng, 2012
 * Find attributes including gene name, accession, sequence, find the peptide position for each cross-linking pair
 *
 */
import java.io.*;
import java.util.*;
public class FindAttri {
	public static void main(String[] args) {
		if (args.length < 3) {
			System.out.println("java -jar filename");
		}
		findAttri("recycled/tmp" + args[0] + "attri","recycled/" + args[1], "recycled/" + args[2]);
	}
	public static void findAttri(String output, String listofuniprot, String input) {                //Extract protein id, accession, description and gene name
		if (input.equals("") || output.equals("") || listofuniprot.equals("")) return;
		String dir = "uniprot/";
		Map<String, ProAttri> attributeList = new HashMap<String, ProAttri>();
		try {
			FileReader fr = new FileReader(listofuniprot);
			BufferedReader br = new BufferedReader(fr);
			String line = br.readLine();
			while (line != null) {
				String uniprot = line.trim();
				FileReader uniprotReader = new FileReader(dir + uniprot + ".txt");
				BufferedReader bufferedUniprotReader = new BufferedReader(uniprotReader);
				String l = bufferedUniprotReader.readLine();
				ProAttri proAttri = new ProAttri();
				attributeList.put(uniprot, proAttri);
				String seq = "";
				while(l != null) {
					//System.out.println(l);//need to comment out later
					if (l.length() < 5) break;
					String buff = l.substring(5);
					if (l.substring(0, 2).equals("ID")) proAttri.id = buff.substring(0, buff.indexOf(' '));
					if (l.substring(0, 2).equals("AC")) proAttri.ac = buff;
					if (l.substring(0, 2).equals("DE")) if (l.substring(5, 12).equals("RecName")) proAttri.de = buff.substring(buff.indexOf('=') + 1, buff.indexOf(';'));
					if (l.substring(0, 2).equals("GN")) 
						if (l.length() > 9 && l.substring(5, 9).equals("Name")) 
							if (buff.indexOf(';') >= 0) proAttri.gn = buff.substring(buff.indexOf('=') + 1, buff.indexOf(';'));
							else if (buff.indexOf(' ') >= 0) proAttri.gn = buff.substring(buff.indexOf('=') + 1, buff.indexOf(' '));
							else proAttri.gn = buff.substring(buff.indexOf('=') + 1);
					if (l.substring(0, 2).equals("  ")) seq += buff;
					l = bufferedUniprotReader.readLine();
				}			
				proAttri.sq = removeBlank(seq);
				bufferedUniprotReader.close();
				uniprotReader.close();
				line = br.readLine();
			}
			br.close();
			fr.close();
		} catch (IOException e) {
			System.out.println(e.getMessage());
		}
		try {
			FileReader fr = new FileReader(input);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(output);
			PrintStream ps = new PrintStream(fout);
			String line = br.readLine();
			while (line != null) {
				String[] arr = line.split("\t");
				ProAttri pa = attributeList.get(arr[1]);
				ProAttri pb = attributeList.get(arr[4]);
				int startPosA = findStartPos(arr[0], pa.sq);
				int startPosB = findStartPos(arr[3], pb.sq);
				if (pa.gn == null) pa.gn = pa.ac;
				if (pb.gn == null) pb.gn = pb.ac;
				ps.println(arr[0] + "\t" + arr[1] + "\t" + arr[2] + "\t" + pa.id + "\t" + pa.ac + "\t" + pa.de + "\t" + pa.gn + "\t" + pa.sq + "\t" + startPosA
						+ "\t" + arr[3] + "\t" + arr[4] + "\t" + arr[5] + "\t" + pb.id + "\t" + pb.ac + "\t" + pb.de + "\t" + pb.gn + "\t" + pb.sq + "\t" + startPosB);
				line = br.readLine();
			}
			br.close();
			fr.close();
			ps.close();
			fout.close();
		} catch (IOException e) {
			System.out.println(e.getMessage());
		}
	}
	public static String removeBlank(String s) {
		StringBuffer sb = new StringBuffer();
		int i = 0;
		while (i < s.length()) {
			if (s.charAt(i) != ' ') {
				 sb.append(s.charAt(i));
			}
			i++;			
		}
		return sb.toString();
	}
	public static int findStartPos(String pep, String pro) {
		int len = pep.length();
		int i = 0;
		while(i + len <= pro.length()) {
			if(pro.substring(i, i + len).equals(pep)) return i;
			i++;
		}
		return -1;
	}
}
class ProAttri {
	public ProAttri() {}
	public String id;
	public String ac;
	public String gn;
	public String de;
	public String sq;	
}
